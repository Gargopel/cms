<?php

namespace Tests\Feature;

use App\Core\Extensions\Discovery\ExtensionDiscoveryService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExtensionDiscoveryServiceTest extends TestCase
{
    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/extensions-'.Str::uuid());
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'plugins');
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'themes');

        config()->set('platform.core.version', '0.1.0');
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->sandboxPath);

        parent::tearDown();
    }

    public function test_it_discovers_valid_plugin_and_theme_manifests(): void
    {
        $this->makeManifest('plugins', 'ValidPlugin', 'plugin.json', [
            'name' => 'Valid Plugin',
            'slug' => 'valid-plugin',
            'description' => 'Plugin valido para o teste.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => [
                'min' => '0.1.0',
            ],
        ]);

        $this->makeManifest('themes', 'ValidTheme', 'theme.json', [
            'name' => 'Valid Theme',
            'slug' => 'valid-theme',
            'description' => 'Tema valido para o teste.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => [
                'min' => '0.1.0',
            ],
        ]);

        $result = $this->makeService()->discover();

        $this->assertCount(1, $result->plugins());
        $this->assertCount(1, $result->themes());
        $this->assertSame(1, $result->summary()['plugins']['valid']);
        $this->assertSame(1, $result->summary()['themes']['valid']);
        $this->assertTrue($result->plugins()[0]->isUsable());
        $this->assertTrue($result->themes()[0]->isUsable());
        $this->assertSame('valid-plugin', $result->plugins()[0]->manifest()?->slug());
        $this->assertSame('valid-theme', $result->themes()[0]->manifest()?->slug());
    }

    public function test_it_marks_invalid_manifests_without_breaking_discovery(): void
    {
        $pluginPath = $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'BrokenPlugin';
        $themePath = $this->sandboxPath.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'BrokenTheme';

        $this->files->ensureDirectoryExists($pluginPath);
        $this->files->ensureDirectoryExists($themePath);
        $this->files->put($pluginPath.DIRECTORY_SEPARATOR.'plugin.json', '{"name": "Broken Plugin"');
        $this->files->put($themePath.DIRECTORY_SEPARATOR.'theme.json', json_encode([
            'name' => 'Broken Theme',
            'description' => 'Sem slug para validar erro.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => [
                'min' => '0.1.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $result = $this->makeService()->discover();

        $this->assertCount(1, $result->plugins());
        $this->assertCount(1, $result->themes());
        $this->assertSame(ExtensionDiscoveryStatus::Invalid, $result->plugins()[0]->status());
        $this->assertSame(ExtensionDiscoveryStatus::Invalid, $result->themes()[0]->status());
        $this->assertFalse($result->plugins()[0]->isUsable());
        $this->assertFalse($result->themes()[0]->isUsable());
        $this->assertNotEmpty($result->plugins()[0]->errors());
        $this->assertNotEmpty($result->themes()[0]->errors());
    }

    public function test_it_marks_incompatible_extensions_without_loading_them(): void
    {
        $this->makeManifest('plugins', 'FuturePlugin', 'plugin.json', [
            'name' => 'Future Plugin',
            'slug' => 'future-plugin',
            'description' => 'Plugin com versao minima acima do core.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => [
                'min' => '9.0.0',
            ],
        ]);

        $result = $this->makeService()->discover();

        $this->assertCount(1, $result->plugins());
        $this->assertSame(ExtensionDiscoveryStatus::Incompatible, $result->plugins()[0]->status());
        $this->assertFalse($result->plugins()[0]->isUsable());
        $this->assertStringContainsString('requires core version', $result->plugins()[0]->errors()[0]);
    }

    public function test_it_keeps_a_normalized_snapshot_and_warnings_for_partially_invalid_manifests(): void
    {
        $this->makeManifest('plugins', 'PartialPlugin', 'plugin.json', [
            'name' => 'Partial Plugin',
            'description' => 'Missing slug but still normalizable in parts.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
            'critical' => 'yes',
            'requires' => ['cms-base', 123, 'cms-base'],
        ]);

        $result = $this->makeService()->discover();
        $extension = $result->plugins()[0];

        $this->assertSame(ExtensionDiscoveryStatus::Invalid, $extension->status());
        $this->assertNotNull($extension->normalizedManifest());
        $this->assertSame('Partial Plugin', $extension->normalizedManifest()['name']);
        $this->assertTrue($extension->normalizedManifest()['critical']);
        $this->assertSame(['cms-base'], $extension->normalizedManifest()['requires']);
        $this->assertNotEmpty($extension->warnings());
    }

    protected function makeService(): ExtensionDiscoveryService
    {
        return new ExtensionDiscoveryService(
            files: $this->files,
            paths: new PlatformPaths($this->sandboxPath),
            validator: app(\App\Core\Extensions\Validation\ExtensionManifestValidator::class),
            normalizer: app(\App\Core\Extensions\Validation\ExtensionManifestNormalizer::class),
        );
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
