<?php

namespace Tests\Feature;

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Install\InstallationState;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Plugins\Pages\Models\Page;
use Tests\TestCase;

class PagesPluginTest extends TestCase
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

        $this->installMarkerPath = storage_path('framework/testing/pages-plugin-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
        $this->bootPagesPlugin();
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

    public function test_admin_pages_routes_require_authentication(): void
    {
        $response = $this->get($this->adminPagesIndexPath());

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_pages_routes_require_plugin_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
        ]);

        $response = $this->actingAs($user)->get($this->adminPagesIndexPath());

        $response->assertForbidden();
    }

    public function test_it_can_create_edit_publish_and_delete_pages_with_real_permissions(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'pages.view_pages',
            'pages.create_pages',
            'pages.edit_pages',
            'pages.publish_pages',
            'pages.delete_pages',
        ]);

        $createResponse = $this->actingAs($user)->post($this->adminPagesStorePath(), [
            'title' => 'About Platform',
            'slug' => 'about-platform',
            'content' => 'Initial content',
            'status' => 'published',
        ]);

        $createResponse->assertRedirect($this->adminPagesIndexPath());
        $this->assertDatabaseHas('plugin_pages_pages', [
            'slug' => 'about-platform',
            'status' => 'published',
        ]);

        $page = Page::query()->where('slug', 'about-platform')->firstOrFail();

        $updateResponse = $this->actingAs($user)->put($this->adminPagesUpdatePath($page), [
            'title' => 'About Platform Updated',
            'slug' => 'about-platform',
            'content' => 'Updated content',
            'status' => 'draft',
        ]);

        $updateResponse->assertRedirect($this->adminPagesIndexPath());
        $this->assertDatabaseHas('plugin_pages_pages', [
            'slug' => 'about-platform',
            'title' => 'About Platform Updated',
            'status' => 'draft',
        ]);

        $deleteResponse = $this->actingAs($user)->delete($this->adminPagesDestroyPath($page));

        $deleteResponse->assertRedirect($this->adminPagesIndexPath());
        $this->assertDatabaseMissing('plugin_pages_pages', [
            'slug' => 'about-platform',
        ]);
    }

    public function test_editor_without_publish_permission_cannot_change_public_status(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'pages.view_pages',
            'pages.create_pages',
            'pages.edit_pages',
        ]);

        $this->actingAs($user)->post($this->adminPagesStorePath(), [
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'content' => 'Draft content',
            'status' => 'published',
        ])->assertRedirect($this->adminPagesIndexPath());

        $page = Page::query()->where('slug', 'draft-page')->firstOrFail();
        $this->assertSame('draft', $page->status->value);

        $published = Page::query()->create([
            'title' => 'Published Page',
            'slug' => 'published-page',
            'content' => 'Published content',
            'status' => 'published',
        ]);

        $this->actingAs($user)->put($this->adminPagesUpdatePath($published), [
            'title' => 'Published Page Edited',
            'slug' => 'published-page',
            'content' => 'Edited content',
            'status' => 'draft',
        ])->assertRedirect($this->adminPagesIndexPath());

        $this->assertSame('published', $published->fresh()->status->value);
    }

    public function test_public_route_only_renders_published_pages(): void
    {
        Page::query()->create([
            'title' => 'Published',
            'slug' => 'published',
            'content' => 'Visible content',
            'status' => 'published',
        ]);

        Page::query()->create([
            'title' => 'Draft',
            'slug' => 'draft',
            'content' => 'Hidden content',
            'status' => 'draft',
        ]);

        $this->get($this->publicPagePath('published'))
            ->assertOk()
            ->assertSee('Published')
            ->assertSee('Visible content');

        $this->get($this->publicPagePath('draft'))
            ->assertNotFound();
    }

    public function test_public_route_uses_theme_override_and_falls_back_to_plugin_view(): void
    {
        $page = Page::query()->create([
            'title' => 'Theme Ready',
            'slug' => 'theme-ready',
            'content' => 'Theme content',
            'status' => 'published',
        ]);

        $this->get($this->publicPagePath($page->slug))
            ->assertOk()
            ->assertSee('Pages Plugin')
            ->assertSee('Theme Ready');

        $themePath = storage_path('framework/testing/themes/pages-override');
        $this->temporaryThemePaths[] = $themePath;

        File::ensureDirectoryExists($themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages');
        File::put(
            $themePath.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.'show.blade.php',
            <<<'BLADE'
<html>
<body>
    <h1>Theme Override For {{ $page->title }}</h1>
</body>
</html>
BLADE
        );

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'pages-theme',
            'name' => 'Pages Theme',
            'description' => 'Temporary theme for tests.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $themePath,
            'manifest_path' => $themePath.DIRECTORY_SEPARATOR.'theme.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        app(CoreSettingsManager::class)->put('active_theme_slug', 'pages-theme', CoreSettingType::String, 'themes');

        $this->get($this->publicPagePath($page->slug))
            ->assertOk()
            ->assertSee('Theme Override For Theme Ready')
            ->assertDontSee('Pages Plugin');
    }

    public function test_plugin_contributes_menu_and_dashboard_surfaces_when_user_can_view_pages(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_dashboard',
            'pages.view_pages',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Pages');
        $response->assertSee('Pages Library');
    }

    protected function bootPagesPlugin(): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'pages');
        app(ExtensionOperationalStateManager::class)->enable(ExtensionType::Plugin, 'pages');
        app(PluginProviderBootstrapper::class)->bootstrap();
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'pages-plugin-role-'.str()->random(8),
            'name' => 'Pages Plugin Role',
            'description' => 'Role used by Pages plugin tests.',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    protected function adminPagesIndexPath(): string
    {
        return '/'.trim((string) config('platform.admin.prefix', 'admin'), '/').'/pages';
    }

    protected function adminPagesStorePath(): string
    {
        return $this->adminPagesIndexPath();
    }

    protected function adminPagesUpdatePath(Page $page): string
    {
        return $this->adminPagesIndexPath().'/'.$page->getKey();
    }

    protected function adminPagesDestroyPath(Page $page): string
    {
        return $this->adminPagesUpdatePath($page);
    }

    protected function publicPagePath(string $slug): string
    {
        return '/pages/'.$slug;
    }
}
