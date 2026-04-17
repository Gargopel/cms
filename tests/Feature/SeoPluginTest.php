<?php

namespace Tests\Feature;

use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Core\Install\InstallationState;
use App\Core\Media\Models\MediaAsset;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Plugins\Blog\Models\Post;
use Plugins\Pages\Models\Page;
use Plugins\Seo\Contracts\SeoMetadataResolver;
use Tests\TestCase;

class SeoPluginTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    /**
     * @var array<int, string>
     */
    protected array $temporaryThemePaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/seo-plugin-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        Storage::fake('public');
        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
        $this->bootPlugins(['pages', 'blog', 'seo']);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        foreach ($this->temporaryThemePaths as $path) {
            File::deleteDirectory($path);
        }

        parent::tearDown();
    }

    public function test_it_resolves_seo_defaults_and_simple_robots_behavior(): void
    {
        app(PluginSettingsManager::class)->update('seo', [
            'default_meta_title_suffix' => ' | Search Layer',
            'default_meta_description' => 'Default platform description.',
            'indexing_enabled' => true,
        ]);

        $metadata = app(SeoMetadataResolver::class)->resolve([
            'title' => 'About Platform',
            'canonical' => 'https://example.test/about-platform',
        ]);

        $this->assertSame('About Platform | Search Layer', $metadata->title);
        $this->assertSame('Default platform description.', $metadata->description);
        $this->assertSame('https://example.test/about-platform', $metadata->canonical);
        $this->assertSame('index, follow', $metadata->robots);

        $noindex = app(SeoMetadataResolver::class)->resolve([
            'title' => 'Private',
            'noindex' => true,
        ]);

        $this->assertSame('noindex, nofollow', $noindex->robots);
    }

    public function test_seo_plugin_settings_route_requires_plugin_permission(): void
    {
        $extension = ExtensionRecord::query()->where('slug', 'seo')->firstOrFail();

        $viewer = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'view_extensions',
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.extensions.settings.edit', $extension))
            ->assertForbidden();

        $manager = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'view_extensions',
            'seo.manage_settings',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.extensions.settings.edit', $extension))
            ->assertOk()
            ->assertSee('Seo Settings')
            ->assertSee('Default Meta Title Suffix');
    }

    public function test_pages_public_route_renders_seo_metadata_from_plugin(): void
    {
        app(PluginSettingsManager::class)->update('seo', [
            'default_meta_title_suffix' => ' | Search Layer',
            'default_meta_description' => 'Default platform description.',
            'indexing_enabled' => true,
        ]);

        $asset = $this->createImageAsset('page-seo.jpg');

        Page::query()->create([
            'title' => 'About Platform',
            'slug' => 'about-platform',
            'content' => 'This is the public About page for the platform, focused on operations, plugins and extensibility.',
            'status' => 'published',
            'featured_image_id' => $asset->getKey(),
        ]);

        $this->get('/pages/about-platform')
            ->assertOk()
            ->assertSee('<title>About Platform | Search Layer</title>', false)
            ->assertSee('<meta name="description" content="This is the public About page for the platform, focused on operations, plugins and extensibility.">', false)
            ->assertSee('<link rel="canonical" href="'.url('/pages/about-platform').'">', false)
            ->assertSee('<meta property="og:image" content="'.$asset->url().'">', false);
    }

    public function test_blog_public_route_renders_seo_metadata_from_plugin(): void
    {
        app(PluginSettingsManager::class)->update('seo', [
            'default_meta_title_suffix' => ' | Search Layer',
            'default_meta_description' => 'Default platform description.',
            'indexing_enabled' => true,
        ]);

        $asset = $this->createImageAsset('post-seo.jpg');

        Post::query()->create([
            'title' => 'Launch Notes',
            'slug' => 'launch-notes',
            'excerpt' => 'Short editorial summary for the launch note.',
            'content' => 'Longer launch note body.',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_id' => $asset->getKey(),
        ]);

        $this->get('/blog/launch-notes')
            ->assertOk()
            ->assertSee('<title>Launch Notes | Search Layer</title>', false)
            ->assertSee('<meta name="description" content="Short editorial summary for the launch note.">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:image" content="'.$asset->url().'">', false);
    }

    public function test_theme_override_can_consume_resolved_seo_metadata(): void
    {
        app(PluginSettingsManager::class)->update('seo', [
            'default_meta_title_suffix' => ' | Search Layer',
            'default_meta_description' => 'Default platform description.',
            'indexing_enabled' => true,
        ]);

        Page::query()->create([
            'title' => 'Theme Seo Page',
            'slug' => 'theme-seo-page',
            'content' => 'Theme aware page content.',
            'status' => 'published',
        ]);

        $themePath = storage_path('framework/testing/themes/seo-override');
        $this->temporaryThemePaths[] = $themePath;

        File::ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages');
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'show.blade.php',
            <<<'BLADE'
<html>
<head>
    <title>{{ $seo->title }}</title>
    <meta name="description" content="{{ $seo->description }}">
</head>
<body>
    <h1>Theme SEO Override {{ $page->title }}</h1>
</body>
</html>
BLADE
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'seo-theme',
            'name' => 'Seo Theme',
            'description' => 'Temporary theme for SEO tests.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'seo-theme', CoreSettingType::String, 'themes');

        $this->get('/pages/theme-seo-page')
            ->assertOk()
            ->assertSee('<title>Theme Seo Page | Search Layer</title>', false)
            ->assertSee('<meta name="description" content="Theme aware page content.">', false)
            ->assertSee('Theme SEO Override Theme Seo Page');
    }

    protected function bootPlugins(array $slugs): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();

        foreach ($slugs as $slug) {
            app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, $slug);

            $record = ExtensionRecord::query()->where('slug', $slug)->firstOrFail();

            if (in_array($slug, ['pages', 'blog'], true)) {
                app(PluginMigrationService::class)->runPendingFor($record);
            }

            app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, $slug);
        }

        app(PluginProviderBootstrapper::class)->bootstrap();
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = \App\Core\Auth\Models\Role::query()->create([
            'scope' => 'tests',
            'slug' => 'seo-plugin-role-'.str()->random(8),
            'name' => 'Seo Plugin Role',
            'description' => 'Role used by SEO plugin tests.',
        ]);

        $permissionIds = \App\Core\Auth\Models\Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    protected function createImageAsset(string $name): MediaAsset
    {
        $path = 'media/2026/04/'.$name;
        Storage::disk('public')->put($path, 'image-binary');

        return MediaAsset::query()->create([
            'disk' => 'public',
            'original_name' => $name,
            'stored_name' => $name,
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'extension' => 'jpg',
            'uploaded_by' => null,
        ]);
    }
}
