<?php

namespace Tests\Feature;

use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Themes\ThemeViewResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Plugins\Blog\Models\Post;
use Plugins\Forms\Models\Form;
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

    public function test_home_renders_rich_blog_sidebar_slot_from_enabled_plugin_contribution(): void
    {
        $record = $this->createBlogPluginRecord(ExtensionOperationalStatus::Enabled);
        app(PluginMigrationService::class)->runPendingFor($record);

        Post::query()->create([
            'title' => 'Launch Story',
            'slug' => 'launch-story',
            'excerpt' => 'Recent post excerpt.',
            'content' => 'Recent post content.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Recent Blog Posts');
        $response->assertSee('Launch Story');
        $response->assertSee('Browse all posts');
    }

    public function test_home_renders_forms_cta_slot_from_enabled_plugin_contribution(): void
    {
        $record = $this->createFormsPluginRecord(ExtensionOperationalStatus::Enabled);
        app(PluginMigrationService::class)->runPendingFor($record);

        Form::query()->create([
            'title' => 'Talk To Sales',
            'slug' => 'talk-to-sales',
            'description' => 'Simple lead capture for teams evaluating the platform.',
            'success_message' => 'Thanks for reaching out.',
            'status' => 'published',
        ]);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Forms Plugin Slot');
        $response->assertSee('Talk To Sales');
        $response->assertSee('Open Form');
    }

    public function test_home_does_not_render_slot_contributions_from_disabled_plugin(): void
    {
        $this->createBlogPluginRecord(ExtensionOperationalStatus::Disabled);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Recent Blog Posts');
        $response->assertDontSee('Browse all posts');
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

        $record = $this->createBlogPluginRecord(ExtensionOperationalStatus::Enabled);
        app(PluginMigrationService::class)->runPendingFor($record);

        Post::query()->create([
            'title' => 'Wrapped Story',
            'slug' => 'wrapped-story',
            'excerpt' => 'Wrapped recent post excerpt.',
            'content' => 'Wrapped recent post content.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Slot Theme');
        $response->assertSee('Theme Slot Wrapper');
        $response->assertSee('Blog Plugin Slot');
        $response->assertSee('Explorar Blog');
    }

    public function test_theme_can_override_the_block_view_for_a_rich_slot_contribution(): void
    {
        $themePath = $this->sandboxPath.DIRECTORY_SEPARATOR.'BlockOverrideTheme';
        $viewsPath = $themePath.DIRECTORY_SEPARATOR.'views';
        $pluginSlotPath = $viewsPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'blog'.DIRECTORY_SEPARATOR.'slots';

        $this->files->ensureDirectoryExists($pluginSlotPath);
        $this->files->put(
            $viewsPath.DIRECTORY_SEPARATOR.'home.blade.php',
            '<html><body><h1>Block Override Theme</h1>{!! $sidebarSlot !!}</body></html>'
        );
        $this->files->put(
            $pluginSlotPath.DIRECTORY_SEPARATOR.'recent-posts.blade.php',
            '<section><strong>Theme Block Override</strong>@foreach ($posts as $post)<div>{{ $post->title }}</div>@endforeach</section>'
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'block-override-theme',
            'name' => 'Block Override Theme',
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
                'name' => 'Block Override Theme',
                'slug' => 'block-override-theme',
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

        app(CoreSettingsManager::class)->put('active_theme_slug', 'block-override-theme', group: 'themes');
        app(ThemeViewResolver::class)->registerActiveThemeNamespace();

        $record = $this->createBlogPluginRecord(ExtensionOperationalStatus::Enabled);
        app(PluginMigrationService::class)->runPendingFor($record);

        Post::query()->create([
            'title' => 'Override Story',
            'slug' => 'override-story',
            'excerpt' => 'Override excerpt.',
            'content' => 'Override content.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(PluginProviderBootstrapper::class)->bootstrap();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Block Override Theme');
        $response->assertSee('Theme Block Override');
        $response->assertSee('Override Story');
        $response->assertDontSee('Browse all posts');
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

    protected function createFormsPluginRecord(ExtensionOperationalStatus $operationalStatus): ExtensionRecord
    {
        return ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'forms',
            'name' => 'Forms',
            'description' => 'Official Forms plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/Forms'),
            'manifest_path' => base_path('plugins/Forms/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => $operationalStatus,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Forms',
                'slug' => 'forms',
                'description' => 'Official Forms plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Plugins\\Forms\\Providers\\FormsServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => ['admin_pages'],
            ],
        ]);
    }
}
