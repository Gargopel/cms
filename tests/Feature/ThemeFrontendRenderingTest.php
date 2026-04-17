<?php

namespace Tests\Feature;

use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
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

    public function test_home_renders_footer_cta_slot_from_enabled_plugin_contribution(): void
    {
        $this->createBlogPluginRecord(ExtensionOperationalStatus::Enabled);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Blog Plugin Slot');
        $response->assertSee('Explorar Blog');
    }

    public function test_home_does_not_render_slot_contributions_from_disabled_plugin(): void
    {
        $this->createBlogPluginRecord(ExtensionOperationalStatus::Disabled);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Blog Plugin Slot');
        $response->assertDontSee('Explorar Blog');
    }

    public function test_theme_specific_slot_template_wraps_plugin_contribution_when_supported(): void
    {
        $themePath = $this->sandboxPath.DIRECTORY_SEPARATOR.'SlotTheme';
        $viewsPath = $themePath.DIRECTORY_SEPARATOR.'views';
        $slotsPath = $viewsPath.DIRECTORY_SEPARATOR.'slots';

        $this->files->ensureDirectoryExists($slotsPath);
        $this->files->put(
            $viewsPath.DIRECTORY_SEPARATOR.'home.blade.php',
            '<html><body><h1>Slot Theme</h1>{!! $footerCtaSlot !!}</body></html>'
        );
        $this->files->put(
            $slotsPath.DIRECTORY_SEPARATOR.'footer_cta.blade.php',
            '<section data-slot-wrapper="footer_cta"><strong>Theme Slot Wrapper</strong>@foreach ($blocks as $slotBlock){!! $slotBlock[\'html\'] !!}@endforeach</section>'
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'slot-theme',
            'name' => 'Slot Theme',
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
                'name' => 'Slot Theme',
                'slug' => 'slot-theme',
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

        app(CoreSettingsManager::class)->put('active_theme_slug', 'slot-theme', group: 'themes');
        app(ThemeViewResolver::class)->registerActiveThemeNamespace();

        $this->createBlogPluginRecord(ExtensionOperationalStatus::Enabled);
        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Slot Theme');
        $response->assertSee('Theme Slot Wrapper');
        $response->assertSee('Blog Plugin Slot');
    }

    protected function createBlogPluginRecord(ExtensionOperationalStatus $operationalStatus): ExtensionRecord
    {
        return ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'blog',
            'name' => 'Blog',
            'description' => 'Official Blog plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/Blog'),
            'manifest_path' => base_path('plugins/Blog/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => $operationalStatus,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Blog',
                'slug' => 'blog',
                'description' => 'Official Blog plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Plugins\\Blog\\Providers\\BlogServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => ['admin_pages'],
            ],
        ]);
    }
}
