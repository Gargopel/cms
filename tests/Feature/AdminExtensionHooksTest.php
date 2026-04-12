<?php

namespace Tests\Feature;

use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Permissions\PluginPermissionRegistrySynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminExtensionHooksTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-extension-hooks-installed.json');
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

    public function test_dashboard_consumes_contributions_from_enabled_plugin_with_permission(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'reporting-suite',
            'name' => 'Reporting Suite',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/ReportingSuite'),
            'manifest_path' => base_path('plugins/ReportingSuite/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Reporting Suite',
                'slug' => 'reporting-suite',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\ContributingServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => ['admin_pages'],
                'permissions' => [
                    [
                        'slug' => 'view_console',
                        'name' => 'View Console',
                        'description' => 'Read plugin console.',
                    ],
                ],
            ],
        ]);

        app(PluginPermissionRegistrySynchronizer::class)->sync();
        app(PluginProviderBootstrapper::class)->bootstrap();

        $user = $this->createUserWithPermissionSlugs([
            'access_admin',
            'view_dashboard',
            'reporting-suite.view_console',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Plugin Surfaces');
        $response->assertSee('Reporting Console');
        $response->assertSee('Reporting Snapshot');
    }

    public function test_plugin_contributions_are_hidden_without_required_permission(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'reporting-suite',
            'name' => 'Reporting Suite',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/ReportingSuite'),
            'manifest_path' => base_path('plugins/ReportingSuite/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Reporting Suite',
                'slug' => 'reporting-suite',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\ContributingServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => ['admin_pages'],
                'permissions' => [
                    [
                        'slug' => 'view_console',
                        'name' => 'View Console',
                        'description' => 'Read plugin console.',
                    ],
                ],
            ],
        ]);

        app(PluginPermissionRegistrySynchronizer::class)->sync();
        app(PluginProviderBootstrapper::class)->bootstrap();

        $user = $this->createUserWithPermissionSlugs([
            'access_admin',
            'view_dashboard',
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Reporting Console');
        $response->assertDontSee('Reporting Snapshot');
    }

    protected function createUserWithPermissionSlugs(array $permissionSlugs): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'hook-viewer-'.str()->random(8),
            'name' => 'Hook Viewer',
            'description' => 'Role used by admin hook tests.',
        ]);

        $permissions = collect($permissionSlugs)->map(function (string $slug): array {
            $scope = str_starts_with($slug, 'reporting-suite.') ? 'plugin:reporting-suite' : 'core';

            return [
                'scope' => $scope,
                'slug' => $slug,
                'name' => str($slug)->replace(['.', '_', '-'], ' ')->title()->toString(),
                'description' => 'Permission used by admin extension hook tests.',
            ];
        })->all();

        $permissionIds = app(PermissionSynchronizer::class)
            ->syncPermissions($permissions)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
