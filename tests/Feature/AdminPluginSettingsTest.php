<?php

namespace Tests\Feature;

use App\Core\Audit\Models\AdminAuditLog;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Plugins\Blog\Enums\BlogPermission;
use Tests\TestCase;

class AdminPluginSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-plugin-settings-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
        $this->prepareBlogPlugin();
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        parent::tearDown();
    }

    public function test_plugin_settings_route_requires_authentication(): void
    {
        $response = $this->get(route('admin.extensions.settings.edit', $this->blogRecord()));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_plugin_settings_route_requires_plugin_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_extensions',
        ]);

        $response = $this->actingAs($user)->get(route('admin.extensions.settings.edit', $this->blogRecord()));

        $response->assertForbidden();
    }

    public function test_it_updates_plugin_settings_and_records_audit_entry(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_extensions',
            BlogPermission::ManageSettings->value,
        ]);

        $response = $this->actingAs($user)->put(route('admin.extensions.settings.update', $this->blogRecord()), [
            'blog_title' => 'Operations Journal',
            'blog_intro' => 'Editorial stream',
            'show_excerpts' => '0',
        ]);

        $response->assertRedirect(route('admin.extensions.settings.edit', $this->blogRecord()));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('core_settings', [
            'group_name' => 'plugin:blog',
            'key_name' => 'blog_title',
            'value' => 'Operations Journal',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.plugin_settings.updated',
            'user_id' => $user->id,
        ]);
    }

    public function test_extensions_index_exposes_settings_action_for_user_with_plugin_settings_permission(): void
    {
        $user = $this->createUserWithPermissions([
            'access_admin',
            'view_extensions',
            BlogPermission::ManageSettings->value,
        ]);

        $response = $this->actingAs($user)->get(route('admin.extensions.index'));

        $response->assertOk();
        $response->assertSee('Settings');
        $response->assertSee('plugin:blog');
        $response->assertSee('blog.manage_settings');
    }

    protected function prepareBlogPlugin(): void
    {
        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'blog');
        app(PluginProviderBootstrapper::class)->bootstrap();
    }

    protected function blogRecord(): ExtensionRecord
    {
        return ExtensionRecord::query()->where('slug', 'blog')->firstOrFail();
    }

    protected function createUserWithPermissions(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'plugin-settings-role-'.str()->random(8),
            'name' => 'Plugin Settings Role',
            'description' => 'Role used by plugin settings tests.',
        ]);

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
