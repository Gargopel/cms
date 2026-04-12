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
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Install\InstallationState;
use App\Models\User;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPluginMigrationOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/admin-plugin-migrations-installed.json');
        config()->set('platform.install.marker_path', $this->installMarkerPath);

        app(InstallationState::class)->clear();
        app(InstallationState::class)->markInstalled([
            'installed_at' => now()->toIso8601String(),
            'core_version' => config('platform.core.version'),
        ]);

        $this->seed(\Database\Seeders\CoreAdminSecuritySeeder::class);

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/admin-plugin-migrations-'.Str::uuid());
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

    public function test_plugin_migration_action_requires_authentication(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'reports-plugin',
            'name' => 'Reports Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'ReportsPlugin',
            'manifest_path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'ReportsPlugin'.DIRECTORY_SEPARATOR.'plugin.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        $response = $this->post(route('admin.extensions.migrations.run', $record));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_plugin_migration_action_requires_manage_extensions_permission(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'reports-plugin',
            'name' => 'Reports Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'ReportsPlugin',
            'manifest_path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'ReportsPlugin'.DIRECTORY_SEPARATOR.'plugin.json',
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.migrations.run', $record));

        $response->assertForbidden();
    }

    public function test_it_executes_plugin_migrations_from_admin_and_audits_the_action(): void
    {
        $user = $this->createUserWithPermissions([
            CorePermission::AccessAdmin,
            CorePermission::ViewExtensions,
            CorePermission::ManageExtensions,
        ]);

        $this->makePluginWithMigration('ReportsPlugin', 'reports-plugin', '2026_04_12_130000_create_plugin_reports_admin_entries_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_reports_admin_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_reports_admin_entries');
    }
};
PHP);

        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'reports-plugin');

        $record = ExtensionRecord::query()->where('slug', 'reports-plugin')->firstOrFail();
        $response = $this->actingAs($user)->post(route('admin.extensions.migrations.run', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHas('status');
        $this->assertTrue(Schema::hasTable('plugin_reports_admin_entries'));
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.migrations_ran',
            'user_id' => $user->id,
        ]);
    }

    public function test_it_blocks_plugin_migration_execution_for_ineligible_plugin_and_audits_attempt(): void
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
            'path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'BrokenPlugin',
            'manifest_path' => $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'BrokenPlugin'.DIRECTORY_SEPARATOR.'plugin.json',
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => ['Invalid manifest'],
        ]);

        $response = $this->actingAs($user)->post(route('admin.extensions.migrations.run', $record));

        $response->assertRedirect(route('admin.extensions.index'));
        $response->assertSessionHasErrors('extensions');
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'admin.extensions.migrations_blocked',
            'user_id' => $user->id,
        ]);
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'scope' => 'tests',
            'slug' => 'test-plugin-migrations-role-'.str()->random(8),
            'name' => 'Test Plugin Migrations Role',
            'description' => 'Role used by plugin migration admin tests.',
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

    protected function makePluginWithMigration(string $directory, string $slug, string $migrationFile, string $migrationContents): void
    {
        $pluginPath = $this->sandboxPath.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$directory;
        $migrationPath = $pluginPath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';

        $this->files->ensureDirectoryExists($migrationPath);
        $this->files->put(
            $pluginPath.DIRECTORY_SEPARATOR.'plugin.json',
            json_encode([
                'name' => 'Reports Plugin',
                'slug' => $slug,
                'description' => 'Plugin with migrations.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'core' => ['min' => '0.1.0'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        $this->files->put($migrationPath.DIRECTORY_SEPARATOR.$migrationFile, $migrationContents);
    }
}
