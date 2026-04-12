<?php

namespace Tests\Feature;

use App\Core\Audit\Models\AdminAuditLog;
use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminThemesTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(storage_path('framework/testing'));
        $this->installMarkerPath = storage_path('framework/testing/admin-themes-installed-'.Str::uuid().'.json');
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

    public function test_themes_routes_require_authentication(): void
    {
        $response = $this->get(route('admin.themes.index'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_themes_view_requires_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
        ]);

        $response = $this->actingAs($user)->get(route('admin.themes.index'));

        $response->assertForbidden();
    }

    public function test_themes_page_is_accessible_with_view_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewThemes,
        ]);

        $response = $this->actingAs($user)->get(route('admin.themes.index'));

        $response->assertOk();
        $response->assertSee('Discovered Themes');
    }

    public function test_theme_activation_requires_manage_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewThemes,
        ]);

        $theme = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'example-theme',
            'name' => 'Example Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/ExampleTheme'),
            'manifest_path' => base_path('themes/ExampleTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => 'discovered',
            'discovery_errors' => [],
            'raw_manifest' => [],
        ]);

        $response = $this->actingAs($user)->post(route('admin.themes.activate', $theme));

        $response->assertForbidden();
    }

    public function test_valid_theme_can_be_activated_and_is_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewThemes,
            CorePermission::ManageThemes,
        ]);

        $theme = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'example-theme',
            'name' => 'Example Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/ExampleTheme'),
            'manifest_path' => base_path('themes/ExampleTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => 'discovered',
            'discovery_errors' => [],
            'raw_manifest' => [],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Example Theme',
                'slug' => 'example-theme',
                'description' => 'Theme fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'critical' => false,
                'requires' => [],
                'capabilities' => ['assets'],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.themes.activate', $theme));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('core_settings', [
            'group_name' => 'themes',
            'key_name' => 'active_theme_slug',
            'value' => 'example-theme',
        ]);
        $this->assertSame(ExtensionLifecycleStatus::Installed, $theme->fresh()->lifecycle_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.themes.activated',
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_theme_is_blocked_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewThemes,
            CorePermission::ManageThemes,
        ]);

        $theme = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'broken-theme',
            'name' => 'Broken Theme',
            'description' => 'Broken theme.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/BrokenTheme'),
            'manifest_path' => base_path('themes/BrokenTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => 'discovered',
            'discovery_errors' => ['Invalid manifest.'],
            'raw_manifest' => [],
        ]);

        $response = $this->actingAs($user)->post(route('admin.themes.activate', $theme));

        $response->assertRedirect(route('admin.themes.index'));
        $response->assertSessionHasErrors('themes');
        $this->assertDatabaseMissing('core_settings', [
            'group_name' => 'themes',
            'key_name' => 'active_theme_slug',
            'value' => 'broken-theme',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.themes.activation_blocked',
            'user_id' => $user->id,
        ]);
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-themes-role-'.str()->random(8),
            'name' => 'Test Themes Role',
            'description' => 'Role used by themes admin tests.',
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
