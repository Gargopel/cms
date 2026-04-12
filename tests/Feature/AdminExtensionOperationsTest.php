<?php

namespace Tests\Feature;

use App\Core\Audit\Models\AdminAuditLog;
use App\Core\Auth\Enums\CorePermission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Install\InstallationState;
use App\Models\User;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminExtensionOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-extensions-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/admin-extensions-'.Str::uuid());
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'plugins');
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'themes');
        app()->instance(PlatformPaths::class, new PlatformPaths($this->sandboxPath));
    }

    protected function tearDown(): void
    {
        if (File::exists($this->installMarkerPath)) {
            File::delete($this->installMarkerPath);
        }

        $this->files->deleteDirectory($this->sandboxPath);

        parent::tearDown();
    }

    public function test_extension_actions_require_authentication(): void
    {
        $response = $this->post(route('admin.extensions.sync'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_extension_actions_require_manage_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.sync'));

        $response->assertForbidden();
    }

    public function test_manual_sync_is_successful_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $this->makeManifest('plugins', 'MediaTools', 'plugin.json', [
            'name' => 'Media Tools',
            'slug' => 'media-tools',
            'description' => 'Valid plugin.',
            'version' => '0.1.0',
            'author' => 'Tests',
            'core' => ['min' => '0.1.0'],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.sync'));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('extension_records', [
            'slug' => 'media-tools',
            'type' => ExtensionType::Plugin->value,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.synced',
            'user_id' => $user->id,
        ]);
    }

    public function test_enable_is_allowed_for_valid_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'alpha-plugin',
            'name' => 'Alpha Plugin',
            'description' => 'Valid plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AlphaPlugin'),
            'manifest_path' => base_path('plugins/AlphaPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => [],
            'raw_manifest' => ['provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider'],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Alpha Plugin',
                'slug' => 'alpha-plugin',
                'description' => 'Valid plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.enable', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $this->assertSame(
            ExtensionOperationalStatus::Enabled,
            $record->fresh()->operational_status
        );
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.enabled',
            'user_id' => $user->id,
        ]);
    }

    public function test_enable_is_blocked_when_required_extension_is_missing_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
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
            'operational_status' => ExtensionOperationalStatus::Discovered,
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
                'requires' => ['seo-kit'],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.enable', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionOperationalStatus::Discovered, $record->fresh()->operational_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.enable_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_enable_is_blocked_for_invalid_or_incompatible_extensions(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $invalid = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'description' => 'Invalid plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => ['Invalid manifest.'],
            'raw_manifest' => [],
        ]);

        $incompatible = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'locked-theme',
            'name' => 'Locked Theme',
            'description' => 'Incompatible theme.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/LockedTheme'),
            'manifest_path' => base_path('themes/LockedTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Incompatible,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => ['Core version mismatch.'],
            'raw_manifest' => [],
        ]);

        $invalidResponse = $this->actingAs($user)->post(route('admin.extensions.enable', $invalid));
        $invalidResponse->assertRedirect(route('admin.extensions.index'));
        $invalidResponse->assertSessionHasErrors('extensions');

        $incompatibleResponse = $this->actingAs($user)->post(route('admin.extensions.enable', $incompatible));
        $incompatibleResponse->assertRedirect(route('admin.extensions.index'));
        $incompatibleResponse->assertSessionHasErrors('extensions');

        $this->assertSame(ExtensionOperationalStatus::Discovered, $invalid->fresh()->operational_status);
        $this->assertSame(ExtensionOperationalStatus::Discovered, $incompatible->fresh()->operational_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.enable_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_disable_of_enabled_extension_is_successful_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'studio-theme',
            'name' => 'Studio Theme',
            'description' => 'Enabled theme.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/StudioTheme'),
            'manifest_path' => base_path('themes/StudioTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => [],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.disable', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $this->assertSame(
            ExtensionOperationalStatus::Disabled,
            $record->fresh()->operational_status
        );
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.disabled',
            'user_id' => $user->id,
        ]);
    }

    public function test_disable_is_blocked_when_active_dependents_exist_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $dependency = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'cms-base',
            'name' => 'CMS Base',
            'description' => 'Base fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CmsBase'),
            'manifest_path' => base_path('plugins/CmsBase/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'CMS Base',
                'slug' => 'cms-base',
                'description' => 'Base fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'page-builder',
            'name' => 'Page Builder',
            'description' => 'Dependent fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/PageBuilder'),
            'manifest_path' => base_path('plugins/PageBuilder/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Page Builder',
                'slug' => 'page-builder',
                'description' => 'Dependent fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => ['cms-base'],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.disable', $dependency));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionOperationalStatus::Enabled, $dependency->fresh()->operational_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.disable_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_disable_is_blocked_for_critical_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'critical-plugin',
            'name' => 'Critical Plugin',
            'description' => 'Critical plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CriticalPlugin'),
            'manifest_path' => base_path('plugins/CriticalPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => ['critical' => true],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Critical Plugin',
                'slug' => 'critical-plugin',
                'description' => 'Critical plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => true,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.disable', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(
            ExtensionOperationalStatus::Enabled,
            $record->fresh()->operational_status
        );
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.disable_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_redundant_enable_is_blocked_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'already-enabled',
            'name' => 'Already Enabled',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AlreadyEnabled'),
            'manifest_path' => base_path('plugins/AlreadyEnabled/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => ['provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider'],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Already Enabled',
                'slug' => 'already-enabled',
                'description' => 'Plugin fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.enable', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.enable_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_install_is_allowed_for_valid_discovered_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'media-tools',
            'name' => 'Media Tools',
            'description' => 'Valid plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/MediaTools'),
            'manifest_path' => base_path('plugins/MediaTools/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Media Tools',
                'slug' => 'media-tools',
                'description' => 'Valid plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.install', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $this->assertSame(ExtensionLifecycleStatus::Installed, $record->fresh()->lifecycle_status);
        $this->assertSame(ExtensionOperationalStatus::Disabled, $record->fresh()->operational_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.installed',
            'user_id' => $user->id,
        ]);
    }

    public function test_install_is_blocked_for_invalid_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'description' => 'Invalid plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => ['Invalid manifest.'],
            'raw_manifest' => [],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.install', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionLifecycleStatus::Discovered, $record->fresh()->lifecycle_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.install_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_is_allowed_for_disabled_installed_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'studio-theme',
            'name' => 'Studio Theme',
            'description' => 'Disabled theme.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/StudioTheme'),
            'manifest_path' => base_path('themes/StudioTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Studio Theme',
                'slug' => 'studio-theme',
                'description' => 'Disabled theme.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.remove', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $this->assertSame(ExtensionLifecycleStatus::Removed, $record->fresh()->lifecycle_status);
        $this->assertSame(ExtensionOperationalStatus::Disabled, $record->fresh()->operational_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.removed',
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_is_blocked_for_enabled_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'enabled-plugin',
            'name' => 'Enabled Plugin',
            'description' => 'Enabled plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/EnabledPlugin'),
            'manifest_path' => base_path('plugins/EnabledPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Enabled Plugin',
                'slug' => 'enabled-plugin',
                'description' => 'Enabled plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.remove', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionLifecycleStatus::Installed, $record->fresh()->lifecycle_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.remove_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_is_blocked_when_active_dependents_exist_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $dependency = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'cms-base',
            'name' => 'CMS Base',
            'description' => 'Base fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CmsBase'),
            'manifest_path' => base_path('plugins/CmsBase/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'CMS Base',
                'slug' => 'cms-base',
                'description' => 'Base fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'page-builder',
            'name' => 'Page Builder',
            'description' => 'Dependent fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/PageBuilder'),
            'manifest_path' => base_path('plugins/PageBuilder/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Page Builder',
                'slug' => 'page-builder',
                'description' => 'Dependent fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => ['cms-base'],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.remove', $dependency));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionLifecycleStatus::Installed, $dependency->fresh()->lifecycle_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.remove_blocked',
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_is_blocked_for_critical_extension_and_audited(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'critical-plugin',
            'name' => 'Critical Plugin',
            'description' => 'Critical plugin.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CriticalPlugin'),
            'manifest_path' => base_path('plugins/CriticalPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Critical Plugin',
                'slug' => 'critical-plugin',
                'description' => 'Critical plugin.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => true,
                'requires' => [],
                'capabilities' => [],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.remove', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertSame(ExtensionLifecycleStatus::Installed, $record->fresh()->lifecycle_status);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.remove_blocked',
            'user_id' => $user->id,
        ]);
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-extensions-role-'.str()->random(8),
            'name' => 'Test Extensions Role',
            'description' => 'Role used by extension admin tests.',
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

    protected function makeManifest(string $root, string $directory, string $fileName, array $manifest): void
    {
        $path = $this->sandboxPath.DIRECTORY_SEPARATOR.$root.DIRECTORY_SEPARATOR.$directory;

        $this->files->ensureDirectoryExists($path);
        $this->files->put(
            $path.DIRECTORY_SEPARATOR.$fileName,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
