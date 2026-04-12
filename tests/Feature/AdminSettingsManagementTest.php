<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Install\InstallationState;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Settings\Enums\CoreSettingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/settings-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        parent::tearDown();
    }

    public function test_settings_routes_require_authentication(): void
    {
        $response = $this->get(route('admin.settings.edit'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_settings_view_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.settings.edit'));

        $response->assertForbidden();
    }

    public function test_settings_manager_reads_defaults_and_persists_typed_values(): void
    {
        $manager = app(CoreSettingsManager::class);

        $this->assertSame(config('app.name'), $manager->get('site_name'));

        $manager->put('feature_toggle', true, CoreSettingType::Boolean, 'tests');
        $manager->put('banner_text', 'Operational note', CoreSettingType::Text, 'tests');

        $this->assertTrue($manager->get('feature_toggle', group: 'tests'));
        $this->assertSame('Operational note', $manager->get('banner_text', group: 'tests'));
    }

    public function test_settings_page_is_accessible_with_view_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewSettings,
        ]);

        $response = $this->actingAs($user)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee('Global Settings');
        $response->assertSee('Read Only');
    }

    public function test_settings_update_requires_manage_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewSettings,
        ]);

        $response = $this->actingAs($user)->put(route('admin.settings.update'), [
            'site_name' => 'Platform One',
            'site_tagline' => 'Operations first',
            'system_email' => 'ops@example.test',
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'footer_text' => 'Footer',
            'global_scripts' => '',
        ]);

        $response->assertForbidden();
    }

    public function test_it_updates_global_settings_via_admin(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewSettings,
            CorePermission::ManageSettings,
        ]);

        $response = $this->actingAs($user)->put(route('admin.settings.update'), [
            'site_name' => 'Operations CMS',
            'site_tagline' => 'Premium control center',
            'system_email' => 'platform@example.test',
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'footer_text' => 'Copyright 2026 Operations CMS',
            'global_scripts' => '<script>window.platform=true;</script>',
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $response->assertSessionHas('status');

        $manager = app(CoreSettingsManager::class);

        $this->assertSame('Operations CMS', $manager->get('site_name'));
        $this->assertSame('Premium control center', $manager->get('site_tagline'));
        $this->assertSame('platform@example.test', $manager->get('system_email'));
        $this->assertSame('America/Sao_Paulo', $manager->get('timezone'));
        $this->assertSame('pt_BR', $manager->get('locale'));
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-settings-role-'.str()->random(8),
            'name' => 'Test Settings Role',
            'description' => 'Role used by settings tests.',
        ]);

        $permissionIds = app(PermissionSynchronizer::class)
            ->syncPermissions(
                collect($permissions)->map(static fn (CorePermission $permission): array => [
                    'scope' => 'core',
                    'slug' => $permission->value,
                    'name' => $permission->label(),
                    'description' => $permission->description(),
                ])->all(),
            )
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
