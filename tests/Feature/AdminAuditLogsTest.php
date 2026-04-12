<?php

namespace Tests\Feature;

use App\Core\Audit\AdminAuditLogger;
use App\Core\Audit\Models\AdminAuditLog;
use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/audit-installed.json');
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

    public function test_audit_logger_persists_log_entry(): void
    {
        $user = User::factory()->create();

        $log = app(AdminAuditLogger::class)->log(
            action: 'core.audit.test',
            actor: $user,
            target: $user,
            summary: 'Audit test entry',
            metadata: ['channel' => 'tests'],
        );

        $this->assertNotNull($log);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'core.audit.test',
            'user_id' => $user->id,
            'summary' => 'Audit test entry',
        ]);
    }

    public function test_audit_logs_route_requires_authentication(): void
    {
        $response = $this->get(route('admin.audit.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_audit_logs_route_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.audit.index'));

        $response->assertForbidden();
    }

    public function test_audit_logs_route_is_accessible_with_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewAuditLogs,
        ]);

        AdminAuditLog::query()->create([
            'action' => 'admin.user.updated',
            'user_id' => $user->id,
            'summary' => 'Updated a user.',
            'metadata' => ['changed' => ['name']],
        ]);

        $response = $this->actingAs($user)->get(route('admin.audit.index'));

        $response->assertOk();
        $response->assertSee('Audit Logs');
        $response->assertSee('admin.user.updated');
    }

    public function test_sensitive_actions_are_logged(): void
    {
        $manageUsersPermission = Permission::query()->where('slug', CorePermission::ManageUsers->value)->firstOrFail();

        $role = Role::query()->create([
            'scope' => 'core',
            'slug' => 'ops_manager',
            'name' => 'Ops Manager',
            'description' => 'Operations role.',
        ]);

        $actor = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ManageUsers,
            CorePermission::ManageRoles,
            CorePermission::ManagePermissions,
            CorePermission::ViewSettings,
            CorePermission::ManageSettings,
            CorePermission::ViewMaintenance,
            CorePermission::RunMaintenanceActions,
        ]);

        $this->withHeader('User-Agent', 'AuditTestAgent/1.0')
            ->actingAs($actor)
            ->post(route('admin.users.store'), [
                'name' => 'Audited User',
                'email' => 'audited@example.test',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
                'role_ids' => [$role->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($actor)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Ops Manager Updated',
                'description' => 'Updated role.',
                'permission_ids' => [$manageUsersPermission->id],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->actingAs($actor)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Audited CMS',
                'site_tagline' => 'Tagline',
                'system_email' => 'audit@example.test',
                'timezone' => 'America/Sao_Paulo',
                'locale' => 'pt_BR',
                'footer_text' => 'Footer',
                'global_scripts' => '<script>ignored=true;</script>',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->actingAs($actor)
            ->post(route('admin.maintenance.cache.views-clear'))
            ->assertRedirect(route('admin.maintenance'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.user.created',
            'user_id' => $actor->id,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.role.updated',
            'user_id' => $actor->id,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.settings.updated',
            'user_id' => $actor->id,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.maintenance.views_cleared',
            'user_id' => $actor->id,
        ]);

        $log = AdminAuditLog::query()->where('action', 'admin.settings.updated')->latest('id')->firstOrFail();

        $this->assertArrayNotHasKey('global_scripts', $log->metadata ?? []);
        $this->assertSame('America/Sao_Paulo', $log->metadata['timezone'] ?? null);
    }

    public function test_admin_login_and_logout_are_logged(): void
    {
        $this->post(route('admin.login.attempt'), [
            'email' => config('platform.admin.local_admin_email'),
            'password' => config('platform.admin.local_admin_password'),
        ])->assertRedirect(route('admin.dashboard'));

        $admin = User::query()->where('email', config('platform.admin.local_admin_email'))->firstOrFail();

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.auth.login',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.auth.logout',
            'user_id' => $admin->id,
        ]);
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-audit-role-'.str()->random(8),
            'name' => 'Test Audit Role',
            'description' => 'Role used by audit tests.',
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
