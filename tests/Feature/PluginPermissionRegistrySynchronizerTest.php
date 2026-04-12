<?php

namespace Tests\Feature;

use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\Permission;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Permissions\PluginPermissionRegistrySynchronizer;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginPermissionRegistrySynchronizerTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/plugin-permissions-installed.json');
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

    public function test_it_synchronizes_permissions_for_valid_installed_plugin(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'analytics-hub',
            'name' => 'Analytics Hub',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AnalyticsHub'),
            'manifest_path' => base_path('plugins/AnalyticsHub/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Analytics Hub',
                'slug' => 'analytics-hub',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    [
                        'slug' => 'view_reports',
                        'name' => 'View Reports',
                        'description' => 'Read plugin reports.',
                    ],
                    [
                        'slug' => 'manage_reports',
                        'name' => 'Manage Reports',
                        'description' => 'Manage plugin reports.',
                    ],
                ],
            ],
        ]);

        $result = app(PluginPermissionRegistrySynchronizer::class)->sync()->toArray();

        $this->assertSame(1, $result['summary']['eligible_plugins']);
        $this->assertSame(2, $result['summary']['synced_permissions']);
        $this->assertDatabaseHas('permissions', [
            'scope' => 'plugin:analytics-hub',
            'slug' => 'analytics-hub.view_reports',
            'name' => 'View Reports',
        ]);
        $this->assertDatabaseHas('permissions', [
            'scope' => 'plugin:analytics-hub',
            'slug' => 'analytics-hub.manage_reports',
            'name' => 'Manage Reports',
        ]);
    }

    public function test_it_does_not_sync_permissions_for_invalid_incompatible_or_discovered_plugin(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'description' => 'Invalid plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => ['Invalid manifest.'],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Broken Plugin',
                'slug' => 'broken-plugin',
                'description' => 'Invalid plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    ['slug' => 'manage', 'name' => 'Manage'],
                ],
            ],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'old-plugin',
            'name' => 'Old Plugin',
            'description' => 'Incompatible plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/OldPlugin'),
            'manifest_path' => base_path('plugins/OldPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Incompatible,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => ['Core version mismatch.'],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Old Plugin',
                'slug' => 'old-plugin',
                'description' => 'Incompatible plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '99.0.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    ['slug' => 'manage', 'name' => 'Manage'],
                ],
            ],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'discovered-plugin',
            'name' => 'Discovered Plugin',
            'description' => 'Discovered only plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/DiscoveredPlugin'),
            'manifest_path' => base_path('plugins/DiscoveredPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Discovered Plugin',
                'slug' => 'discovered-plugin',
                'description' => 'Discovered only plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    ['slug' => 'manage', 'name' => 'Manage'],
                ],
            ],
        ]);

        $result = app(PluginPermissionRegistrySynchronizer::class)->sync()->toArray();

        $this->assertSame(0, $result['summary']['synced_permissions']);
        $this->assertDatabaseCount('permissions', 0);
    }

    public function test_removed_plugin_permissions_are_pruned_and_roles_remain_compatible(): void
    {
        $plugin = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'analytics-hub',
            'name' => 'Analytics Hub',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AnalyticsHub'),
            'manifest_path' => base_path('plugins/AnalyticsHub/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Analytics Hub',
                'slug' => 'analytics-hub',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    ['slug' => 'view_reports', 'name' => 'View Reports'],
                ],
            ],
        ]);

        app(PluginPermissionRegistrySynchronizer::class)->sync();

        $permission = Permission::query()->where('slug', 'analytics-hub.view_reports')->firstOrFail();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'analytics_reports_role',
            'name' => 'Analytics Reports Role',
            'description' => 'Role used by plugin permission tests.',
        ]);
        $role->permissions()->sync([$permission->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('analytics-hub.view_reports'));

        app(ExtensionLifecycleStateManager::class)->removeRecord($plugin);

        $this->assertDatabaseMissing('permissions', [
            'scope' => 'plugin:analytics-hub',
            'slug' => 'analytics-hub.view_reports',
        ]);
        $this->assertFalse($user->fresh()->hasPermission('analytics-hub.view_reports'));
    }

    public function test_permissions_page_shows_core_and_plugin_origins(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'analytics-hub',
            'name' => 'Analytics Hub',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AnalyticsHub'),
            'manifest_path' => base_path('plugins/AnalyticsHub/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Analytics Hub',
                'slug' => 'analytics-hub',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
                'permissions' => [
                    ['slug' => 'view_reports', 'name' => 'View Reports'],
                ],
            ],
        ]);

        app(PluginPermissionRegistrySynchronizer::class)->sync();
        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);

        $permissionIds = Permission::query()
            ->whereIn('slug', ['access_admin', 'manage_permissions'])
            ->pluck('id')
            ->all();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'permission_auditor',
            'name' => 'Permission Auditor',
            'description' => 'Role used by permission index tests.',
        ]);
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('admin.permissions.index'));

        $response->assertOk();
        $response->assertSee('Core platform');
        $response->assertSee('Plugin analytics-hub');
        $response->assertSee('analytics-hub.view_reports');
    }
}
