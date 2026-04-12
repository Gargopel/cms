<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Enums\CoreRole;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminSecurityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/security-management-installed.json');
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

    public function test_security_routes_require_authentication(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_security_routes_require_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewDashboard,
        ]);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_users_index_is_accessible_with_manage_users_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ManageUsers,
        ]);

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('User Directory');
    }

    public function test_it_can_create_and_edit_a_user_with_roles(): void
    {
        $role = Role::query()->create([
            'scope' => 'core',
            'slug' => 'content_editor',
            'name' => 'Content Editor',
            'description' => 'Editor role.',
        ]);

        $actor = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ManageUsers,
            CorePermission::ManageRoles,
        ]);

        $storeResponse = $this->actingAs($actor)->post(route('admin.users.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role_ids' => [$role->id],
        ]);

        $storeResponse->assertRedirect(route('admin.users.index'));

        /** @var User $user */
        $user = User::query()->where('email', 'jane@example.test')->firstOrFail();
        $this->assertSame('Jane Doe', $user->name);
        $this->assertTrue($user->hasRole('content_editor'));

        $updateResponse = $this->actingAs($actor)->put(route('admin.users.update', $user), [
            'name' => 'Jane Updated',
            'email' => 'jane@example.test',
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [$role->id],
        ]);

        $updateResponse->assertRedirect(route('admin.users.index'));
        $this->assertSame('Jane Updated', $user->fresh()->name);
    }

    public function test_it_can_create_and_edit_a_role_and_assign_permissions(): void
    {
        $actor = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ManageRoles,
            CorePermission::ManagePermissions,
        ]);

        $viewDashboard = Permission::query()->where('slug', CorePermission::ViewDashboard->value)->firstOrFail();
        $manageUsers = Permission::query()->where('slug', CorePermission::ManageUsers->value)->firstOrFail();

        $storeResponse = $this->actingAs($actor)->post(route('admin.roles.store'), [
            'name' => 'Operations Manager',
            'slug' => 'operations_manager',
            'description' => 'Operations role.',
            'permission_ids' => [$viewDashboard->id],
        ]);

        $storeResponse->assertRedirect(route('admin.roles.index'));

        /** @var Role $role */
        $role = Role::query()->where('slug', 'operations_manager')->firstOrFail();
        $this->assertTrue($role->permissions()->whereKey($viewDashboard->id)->exists());

        $updateResponse = $this->actingAs($actor)->put(route('admin.roles.update', $role), [
            'name' => 'Operations Lead',
            'description' => 'Updated role.',
            'permission_ids' => [$viewDashboard->id, $manageUsers->id],
        ]);

        $updateResponse->assertRedirect(route('admin.roles.index'));
        $this->assertSame('Operations Lead', $role->fresh()->name);
        $this->assertTrue($role->permissions()->whereKey($manageUsers->id)->exists());
    }

    public function test_permissions_index_is_accessible_with_manage_permissions_permission(): void
    {
        $actor = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ManagePermissions,
        ]);

        $response = $this->actingAs($actor)->get(route('admin.permissions.index'));

        $response->assertOk();
        $response->assertSee('Permission Catalog');
        $response->assertSee(CorePermission::ManageUsers->value);
    }

    public function test_it_blocks_removing_the_last_super_administrator(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::query()->where('email', config('platform.admin.local_admin_email'))->firstOrFail();

        $response = $this->actingAs($superAdmin)->put(route('admin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'password' => '',
            'password_confirmation' => '',
            'role_ids' => [],
        ]);

        $response->assertSessionHasErrors('role_ids');
        $this->assertTrue($superAdmin->fresh()->hasRole(CoreRole::Administrator->value));
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-role-'.str()->random(8),
            'name' => 'Test Role',
            'description' => 'Role used by security management tests.',
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
