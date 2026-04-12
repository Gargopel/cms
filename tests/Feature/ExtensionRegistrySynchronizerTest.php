<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExtensionRegistrySynchronizerTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/registry-'.Str::uuid());
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'plugins');
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'themes');

        config()->set('platform.core.version', '0.1.0');
        app()->instance(PlatformPaths::class, new PlatformPaths($this->sandboxPath));
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->sandboxPath);

        parent::tearDown();
    }

    public function test_it_synchronizes_discovered_extensions_into_the_registry(): void
    {
        $this->makeManifest('plugins', 'BlogTools', 'plugin.json', [
            'name' => 'Blog Tools',
            'slug' => 'blog-tools',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('themes', 'StudioTheme', 'theme.json', [
            'name' => 'Studio Theme',
            'slug' => 'studio-theme',
            'description' => 'Tema valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $result = app(ExtensionRegistrySynchronizer::class)->sync()->toArray();

        $this->assertSame(2, $result['summary']['created']);
        $this->assertDatabaseCount('extension_records', 2);
        $this->assertDatabaseHas('extension_records', [
            'type' => ExtensionType::Plugin->value,
            'slug' => 'blog-tools',
            'discovery_status' => ExtensionDiscoveryStatus::Valid->value,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered->value,
            'operational_status' => ExtensionOperationalStatus::Discovered->value,
        ]);
        $this->assertDatabaseHas('extension_records', [
            'type' => ExtensionType::Theme->value,
            'slug' => 'studio-theme',
            'discovery_status' => ExtensionDiscoveryStatus::Valid->value,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered->value,
            'operational_status' => ExtensionOperationalStatus::Discovered->value,
        ]);
    }

    public function test_it_updates_detected_version_when_the_manifest_changes(): void
    {
        $this->makeManifest('plugins', 'SeoKit', 'plugin.json', [
            'name' => 'SEO Kit',
            'slug' => 'seo-kit',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $this->makeManifest('plugins', 'SeoKit', 'plugin.json', [
            'name' => 'SEO Kit',
            'slug' => 'seo-kit',
            'description' => 'Plugin valido.',
            'version' => '0.2.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $result = app(ExtensionRegistrySynchronizer::class)->sync()->toArray();

        $this->assertSame(1, $result['summary']['updated']);
        $this->assertDatabaseHas('extension_records', [
            'type' => ExtensionType::Plugin->value,
            'slug' => 'seo-kit',
            'detected_version' => '0.2.0',
        ]);
        $this->assertDatabaseCount('extension_records', 1);
    }

    public function test_it_persists_invalid_extensions_as_invalid(): void
    {
        $path = $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'BrokenPlugin';
        $this->files->ensureDirectoryExists($path);
        $this->files->put($path.DIRECTORY_SEPARATOR.'plugin.json', json_encode([
            'name' => 'Broken Plugin',
            'description' => 'Missing slug but still partially readable.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
            'critical' => true,
            'requires' => ['cms-base', 123],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        app(ExtensionRegistrySynchronizer::class)->sync();

        $record = ExtensionRecord::query()->firstOrFail();

        $this->assertSame(ExtensionDiscoveryStatus::Invalid, $record->discovery_status);
        $this->assertSame(ExtensionLifecycleStatus::Discovered, $record->administrativeLifecycleStatus());
        $this->assertSame(ExtensionOperationalStatus::Discovered, $record->operational_status);
        $this->assertNull($record->slug);
        $this->assertNotEmpty($record->discovery_errors);
        $this->assertSame('Broken Plugin', $record->normalized_manifest['name']);
        $this->assertTrue($record->normalized_manifest['critical']);
        $this->assertSame(['cms-base'], $record->normalized_manifest['requires']);
        $this->assertNotEmpty($record->manifest_warnings);
    }

    public function test_it_persists_incompatible_extensions_as_incompatible(): void
    {
        $this->makeManifest('themes', 'FutureTheme', 'theme.json', [
            'name' => 'Future Theme',
            'slug' => 'future-theme',
            'description' => 'Tema acima da versao do core.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '9.0.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $record = ExtensionRecord::query()->firstOrFail();

        $this->assertSame(ExtensionDiscoveryStatus::Incompatible, $record->discovery_status);
        $this->assertSame(ExtensionLifecycleStatus::Discovered, $record->administrativeLifecycleStatus());
        $this->assertSame(ExtensionOperationalStatus::Discovered, $record->operational_status);
        $this->assertSame('future-theme', $record->slug);
    }

    public function test_it_can_install_enable_and_disable_a_valid_extension_without_booting_it(): void
    {
        $this->makeManifest('plugins', 'MediaTools', 'plugin.json', [
            'name' => 'Media Tools',
            'slug' => 'media-tools',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $lifecycle = app(ExtensionLifecycleStateManager::class);
        $manager = app(ExtensionOperationalStateManager::class);
        $install = $lifecycle->install(ExtensionType::Plugin, 'media-tools')->toArray();
        $enable = $manager->enable(ExtensionType::Plugin, 'media-tools')->toArray();
        $disable = $manager->disable(ExtensionType::Plugin, 'media-tools')->toArray();

        $this->assertTrue($install['success']);
        $this->assertSame(ExtensionLifecycleStatus::Installed->value, $install['record']['lifecycle_status']);
        $this->assertTrue($enable['success']);
        $this->assertTrue($enable['changed']);
        $this->assertSame(ExtensionOperationalStatus::Enabled->value, $enable['record']['operational_status']);
        $this->assertTrue($disable['success']);
        $this->assertTrue($disable['changed']);
        $this->assertSame(ExtensionOperationalStatus::Disabled->value, $disable['record']['operational_status']);
    }

    public function test_it_blocks_enable_for_invalid_or_incompatible_extensions(): void
    {
        $this->makeManifest('plugins', 'BrokenPlugin', 'plugin.json', [
            'name' => 'Broken Plugin',
            'slug' => 'broken-plugin',
            'description' => 'Plugin invalido por versao.',
            'version' => 'broken',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('themes', 'LockedTheme', 'theme.json', [
            'name' => 'Locked Theme',
            'slug' => 'locked-theme',
            'description' => 'Tema incompatível.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '9.0.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $manager = app(ExtensionOperationalStateManager::class);
        $invalidEnable = $manager->enable(ExtensionType::Plugin, 'broken-plugin')->toArray();
        $incompatibleEnable = $manager->enable(ExtensionType::Theme, 'locked-theme')->toArray();

        $this->assertFalse($invalidEnable['success']);
        $this->assertFalse($invalidEnable['changed']);
        $this->assertSame(ExtensionOperationalStatus::Discovered->value, $invalidEnable['record']['operational_status']);
        $this->assertStringContainsString('cannot be enabled', $invalidEnable['message']);
        $this->assertFalse($incompatibleEnable['success']);
        $this->assertFalse($incompatibleEnable['changed']);
        $this->assertSame(ExtensionOperationalStatus::Discovered->value, $incompatibleEnable['record']['operational_status']);
        $this->assertStringContainsString('cannot be enabled', $incompatibleEnable['message']);
    }

    public function test_removed_extension_remains_removed_after_sync_refreshes_manifest_metadata(): void
    {
        $this->makeManifest('plugins', 'SeoKit', 'plugin.json', [
            'name' => 'SEO Kit',
            'slug' => 'seo-kit',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $record = ExtensionRecord::query()->firstOrFail();
        $record->update([
            'lifecycle_status' => ExtensionLifecycleStatus::Removed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
        ]);

        $this->makeManifest('plugins', 'SeoKit', 'plugin.json', [
            'name' => 'SEO Kit',
            'slug' => 'seo-kit',
            'description' => 'Plugin valido atualizado.',
            'version' => '0.2.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $record = $record->fresh();

        $this->assertSame(ExtensionLifecycleStatus::Removed, $record->lifecycle_status);
        $this->assertSame(ExtensionOperationalStatus::Disabled, $record->operational_status);
        $this->assertSame('0.2.0', $record->detected_version);
    }

    protected function makeManifest(string $root, string $directory, string $fileName, array $manifest): void
    {
        $path = $this->sandboxPath.DIRECTORY_SEPARATOR.$root.DIRECTORY_SEPARATOR.$directory;

        $this->files->ensureDirectoryExists($path);
        $this->files->put(
            $path.DIRECTORY_SEPARATOR.$fileName,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
