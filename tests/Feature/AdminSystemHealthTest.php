<?php

namespace Tests\Feature;

use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminSystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-health-installed.json');
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

    public function test_system_health_route_requires_authentication(): void
    {
        $response = $this->get(route('admin.health.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_system_health_route_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.health.index'));

        $response->assertForbidden();
    }

    public function test_system_health_route_is_accessible_with_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewSystemHealth,
        ]);

        $response = $this->actingAs($user)->get(route('admin.health.index'));

        $response->assertOk();
        $response->assertSee('System Health');
        $response->assertSee('Health Checks');
        $response->assertSee('Extensions Ecosystem');
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-health-role-'.str()->random(8),
            'name' => 'Test Health Role',
            'description' => 'Role used by health admin tests.',
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
