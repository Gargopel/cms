<?php

namespace Tests\Feature;

use App\Core\Extensions\Boot\PluginProviderBootstrapper;
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

class PluginProviderBootstrapperTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/boot-'.Str::uuid());
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

    public function test_enabled_valid_plugin_with_valid_provider_is_registered(): void
    {
        $this->makeManifest('plugins', 'AlphaPlugin', 'plugin.json', [
            'name' => 'Alpha Plugin',
            'slug' => 'alpha-plugin',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'alpha-plugin');
        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'alpha-plugin');

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertTrue(app()->bound('tests.plugin.alpha'));
        $this->assertSame(1, $report['summary']['considered']);
        $this->assertSame(1, $report['summary']['registered']);
        $this->assertSame('alpha-plugin', $report['registered'][0]['slug']);
    }

    public function test_disabled_plugin_is_not_booted(): void
    {
        $this->makeManifest('plugins', 'DisabledPlugin', 'plugin.json', [
            'name' => 'Disabled Plugin',
            'slug' => 'disabled-plugin',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertFalse(app()->bound('tests.plugin.alpha'));
        $this->assertSame(0, $report['summary']['considered']);
        $this->assertSame(1, $report['summary']['ignored']);
        $this->assertSame('operational_status_not_enabled', $report['ignored'][0]['reason']);
    }

    public function test_invalid_or_incompatible_plugins_are_not_booted(): void
    {
        $this->makeManifest('plugins', 'InvalidPlugin', 'plugin.json', [
            'name' => 'Invalid Plugin',
            'slug' => 'invalid-plugin',
            'description' => 'Plugin invalido.',
            'version' => 'broken',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('plugins', 'FuturePlugin', 'plugin.json', [
            'name' => 'Future Plugin',
            'slug' => 'future-plugin',
            'description' => 'Plugin incompatível.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableBetaServiceProvider',
            'core' => ['min' => '9.0.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        ExtensionRecord::query()->whereIn('slug', ['invalid-plugin', 'future-plugin'])
            ->update([
                'lifecycle_status' => 'installed',
                'operational_status' => ExtensionOperationalStatus::Enabled->value,
            ]);

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertFalse(app()->bound('tests.plugin.alpha'));
        $this->assertFalse(app()->bound('tests.plugin.beta'));
        $this->assertSame(0, $report['summary']['registered']);
        $this->assertSame(2, $report['summary']['ignored']);
    }

    public function test_theme_is_never_booted_as_provider(): void
    {
        $this->makeManifest('themes', 'ThemeWithProvider', 'theme.json', [
            'name' => 'Theme With Provider',
            'slug' => 'theme-with-provider',
            'description' => 'Tema valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        ExtensionRecord::query()
            ->where('slug', 'theme-with-provider')
            ->update([
                'lifecycle_status' => 'installed',
                'operational_status' => ExtensionOperationalStatus::Enabled->value,
            ]);

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertFalse(app()->bound('tests.plugin.alpha'));
        $this->assertSame('not_a_plugin', $report['ignored'][0]['reason']);
    }

    public function test_provider_missing_or_invalid_is_handled_safely(): void
    {
        $this->makeManifest('plugins', 'MissingProvider', 'plugin.json', [
            'name' => 'Missing Provider',
            'slug' => 'missing-provider',
            'description' => 'Plugin sem provider.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('plugins', 'NonExistingProvider', 'plugin.json', [
            'name' => 'Non Existing Provider',
            'slug' => 'non-existing-provider',
            'description' => 'Plugin com provider ausente.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\MissingServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('plugins', 'WrongProvider', 'plugin.json', [
            'name' => 'Wrong Provider',
            'slug' => 'wrong-provider',
            'description' => 'Plugin com classe incompatível.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\NotAServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('plugins', 'ExplodingProvider', 'plugin.json', [
            'name' => 'Exploding Provider',
            'slug' => 'exploding-provider',
            'description' => 'Plugin com provider que falha.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\ExplodingServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();

        foreach (['missing-provider', 'non-existing-provider', 'wrong-provider', 'exploding-provider'] as $slug) {
            app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, $slug);
            app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, $slug);
        }

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertSame(3, $report['summary']['considered']);
        $this->assertSame(1, $report['summary']['ignored']);
        $this->assertSame(3, $report['summary']['failed']);
        $this->assertSame('provider_missing', $report['ignored'][0]['reason']);
    }

    public function test_multiple_valid_plugins_can_be_registered(): void
    {
        $this->makeManifest('plugins', 'AlphaPlugin', 'plugin.json', [
            'name' => 'Alpha Plugin',
            'slug' => 'alpha-plugin',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        $this->makeManifest('plugins', 'BetaPlugin', 'plugin.json', [
            'name' => 'Beta Plugin',
            'slug' => 'beta-plugin',
            'description' => 'Plugin valido.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'provider' => 'Tests\\Fixtures\\Plugins\\BootableBetaServiceProvider',
            'core' => ['min' => '0.1.0'],
        ]);

        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'alpha-plugin');
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'beta-plugin');
        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'alpha-plugin');
        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'beta-plugin');

        $report = app(PluginProviderBootstrapper::class)->bootstrap()->toArray();

        $this->assertTrue(app()->bound('tests.plugin.alpha'));
        $this->assertTrue(app()->bound('tests.plugin.beta'));
        $this->assertSame(2, $report['summary']['registered']);
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
