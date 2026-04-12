<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Health\Checks\ExtensionsEcosystemHealthCheck;
use App\Core\Health\SystemHealthService;
use App\Core\Install\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected string $installMarkerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installMarkerPath = storage_path('framework/testing/health-installed.json');
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

    public function test_health_service_returns_structured_report(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'health-alpha',
            'name' => 'Health Alpha',
            'description' => 'Health fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/HealthAlpha'),
            'manifest_path' => base_path('plugins/HealthAlpha/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => [],
        ]);

        $report = app(SystemHealthService::class)->run()->toArray();

        $this->assertSame(['total', 'ok', 'warning', 'error'], array_keys($report['summary']));
        $this->assertContains($report['overall_status'], ['ok', 'warning', 'error']);
        $this->assertNotEmpty($report['checks']);
        $this->assertContains('database', array_column($report['checks'], 'key'));
        $this->assertContains('extensions_registry', array_column($report['checks'], 'key'));
        $this->assertContains('extensions_ecosystem', array_column($report['checks'], 'key'));
    }

    public function test_extensions_registry_check_warns_when_invalid_extensions_exist(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'description' => 'Broken fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => ['invalid manifest'],
            'raw_manifest' => [],
        ]);

        $report = app(SystemHealthService::class)->run()->toArray();
        $registryCheck = collect($report['checks'])->firstWhere('key', 'extensions_registry');

        $this->assertNotNull($registryCheck);
        $this->assertSame('warning', $registryCheck['status']);
        $this->assertSame(1, $registryCheck['meta']['invalid']);
    }

    public function test_extensions_ecosystem_check_reports_dependency_and_critical_issues(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'dependency-plugin',
            'name' => 'Dependency Plugin',
            'description' => 'Dependency fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/DependencyPlugin'),
            'manifest_path' => base_path('plugins/DependencyPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Dependency Plugin',
                'slug' => 'dependency-plugin',
                'description' => 'Dependency fixture.',
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

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'consumer-plugin',
            'name' => 'Consumer Plugin',
            'description' => 'Consumer fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/ConsumerPlugin'),
            'manifest_path' => base_path('plugins/ConsumerPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Consumer Plugin',
                'slug' => 'consumer-plugin',
                'description' => 'Consumer fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => ['dependency-plugin'],
                'capabilities' => [],
            ],
        ]);

        $check = app(ExtensionsEcosystemHealthCheck::class)->run()->toArray();

        $this->assertSame('error', $check['status']);
        $this->assertGreaterThan(0, $check['meta']['summary']['issues']);
        $this->assertNotEmpty($check['meta']['top_issues']);
    }
}
