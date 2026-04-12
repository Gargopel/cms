<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Themes\ThemeViewResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThemeFrontendRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/theme-frontend-'.Str::uuid());
        $this->files->ensureDirectoryExists($this->sandboxPath);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->sandboxPath);

        parent::tearDown();
    }

    public function test_active_theme_view_is_applied_on_frontend(): void
    {
        $themePath = $this->sandboxPath.DIRECTORY_SEPARATOR.'ActiveTheme';
        $this->files->ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views');
        $this->files->put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'home.blade.php',
            '<html><body><h1>Themed Home</h1><p>{{ $siteName }}</p></body></html>'
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'active-theme',
            'name' => 'Active Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => 'disabled',
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Active Theme',
                'slug' => 'active-theme',
                'description' => 'Theme fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'active-theme', group: 'themes');
        app(ThemeViewResolver::class)->registerActiveThemeNamespace();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Themed Home');
        $response->assertDontSee('Core Frontend Fallback');
    }

    public function test_frontend_falls_back_to_core_view_when_active_theme_has_no_matching_template(): void
    {
        $themePath = $this->sandboxPath.DIRECTORY_SEPARATOR.'FallbackTheme';
        $this->files->ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views');

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'fallback-theme',
            'name' => 'Fallback Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => 'disabled',
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Fallback Theme',
                'slug' => 'fallback-theme',
                'description' => 'Theme fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'fallback-theme', group: 'themes');
        app(ThemeViewResolver::class)->registerActiveThemeNamespace();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Core Frontend Fallback');
    }
}
