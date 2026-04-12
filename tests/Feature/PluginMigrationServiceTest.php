<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Support\PlatformPaths;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PluginMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Filesystem $files;

    protected string $sandboxPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);
        $this->sandboxPath = storage_path('framework/testing/plugin-migrations-'.Str::uuid());
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'plugins');
        $this->files->ensureDirectoryExists($this->sandboxPath.DIRECTORY_SEPARATOR.'themes');

        app()->instance(PlatformPaths::class, new PlatformPaths($this->sandboxPath));
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->sandboxPath);

        parent::tearDown();
    }

    public function test_it_discovers_plugin_migrations_and_detects_pending_files(): void
    {
        $this->makePluginWithMigration('ReportsPlugin', 'reports-plugin', '2026_04_12_120000_create_plugin_reports_entries_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_reports_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_reports_entries');
    }
};
PHP);

        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'reports-plugin');

        $record = ExtensionRecord::query()->where('slug', 'reports-plugin')->firstOrFail();
        $status = app(PluginMigrationService::class)->statusFor($record);

        $this->assertTrue($status->eligible());
        $this->assertTrue($status->hasMigrationsDirectory());
        $this->assertTrue($status->hasMigrations());
        $this->assertTrue($status->hasPendingMigrations());
        $this->assertTrue($status->canRun());
        $this->assertSame(1, $status->pendingCount());
    }

    public function test_it_executes_pending_plugin_migrations_successfully(): void
    {
        $this->makePluginWithMigration('ReportsPlugin', 'reports-plugin', '2026_04_12_120100_create_plugin_reports_entries_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_reports_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_reports_entries');
    }
};
PHP);

        app(ExtensionRegistrySynchronizer::class)->sync();
        app(ExtensionLifecycleStateManager::class)->install(ExtensionType::Plugin, 'reports-plugin');

        $record = ExtensionRecord::query()->where('slug', 'reports-plugin')->firstOrFail();
        $result = app(PluginMigrationService::class)->runPendingFor($record);

        $this->assertTrue($result->success());
        $this->assertTrue($result->changed());
        $this->assertTrue(Schema::hasTable('plugin_reports_entries'));
        $this->assertSame(0, $result->status()?->pendingCount());
    }

    public function test_it_blocks_migration_execution_for_ineligible_plugin_records(): void
    {
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

        $status = app(PluginMigrationService::class)->statusFor($record);
        $result = app(PluginMigrationService::class)->runPendingFor($record);

        $this->assertFalse($status->eligible());
        $this->assertFalse($status->canRun());
        $this->assertFalse($result->success());
        $this->assertFalse($result->changed());
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
