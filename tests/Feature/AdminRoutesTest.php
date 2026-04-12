<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Boot\PluginBootReport;
use App\Core\Extensions\Boot\PluginBootstrapReportStore;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-installed.json');

        config()->set('platform.install.marker_path', $this->installMarkerPath);
        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        parent::tearDown();
    }

    public function test_admin_routes_redirect_guests_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_accepts_valid_credentials(): void
    {
        $this->seedAdminSecurity();

        $response = $this->post(route('admin.login.attempt'), [
            'email' => 'admin@example.test',
            'password' => 'admin12345',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_without_admin_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_dashboard_is_accessible_with_required_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewDashboard,
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Core Dashboard');
        $response->assertSee('Platform Core');
    }

    public function test_extensions_page_renders_registered_extensions_and_bootstrap_summary(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'alpha-plugin',
            'name' => 'Alpha Plugin',
            'description' => 'Plugin tecnico.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AlphaPlugin'),
            'manifest_path' => base_path('plugins/AlphaPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => ['provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider'],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Alpha Plugin',
                'slug' => 'alpha-plugin',
                'description' => 'Plugin tecnico.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
                'critical' => false,
                'requires' => ['cms-base'],
                'capabilities' => ['widgets', 'custom_bridge'],
            ],
            'manifest_warnings' => [
                'Capability [custom_bridge] is not recognized by the core and will be treated as custom metadata.',
            ],
        ]);

        app(PluginBootstrapReportStore::class)->remember(new PluginBootReport(
            considered: [['slug' => 'alpha-plugin']],
            registered: [['slug' => 'alpha-plugin']],
            ignored: [],
            failed: [],
            systemErrors: [],
        ));

        $response = $this->actingAs($user)->get(route('admin.extensions.index'));

        $response->assertOk();
        $response->assertSee('Alpha Plugin');
        $response->assertSee('alpha-plugin');
        $response->assertSee('Registered');
        $response->assertSee('Lifecycle');
        $response->assertSee('installed for admin use');
        $response->assertSee('Requires: cms-base');
        $response->assertSee('Extensions Health');
        $response->assertSee('Capabilities: Widgets');
        $response->assertSee('Custom capabilities: custom_bridge');
    }

    public function test_maintenance_view_requires_specific_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.maintenance'));

        $response->assertForbidden();
    }

    public function test_maintenance_actions_require_run_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMaintenance,
        ]);

        cache()->put('admin-maintenance-test', 'ready', 60);

        $response = $this->actingAs($user)->post(route('admin.maintenance.cache.application-clear'));

        $response->assertForbidden();
        $this->assertSame('ready', cache()->get('admin-maintenance-test'));
    }

    public function test_maintenance_actions_are_available_with_proper_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewMaintenance,
            CorePermission::RunMaintenanceActions,
        ]);

        cache()->put('admin-maintenance-test', 'ready', 60);

        $response = $this->actingAs($user)->post(route('admin.maintenance.cache.application-clear'));

        $response->assertRedirect(route('admin.maintenance'));
        $response->assertSessionHas('status');
        $this->assertNull(cache()->get('admin-maintenance-test'));
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $this->seedAdminSecurity();

        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-admin-role-'.str()->random(8),
            'name' => 'Test Admin Role',
            'description' => 'Role used by feature tests.',
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

    protected function seedAdminSecurity(): void
    {
        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);
    }
}
