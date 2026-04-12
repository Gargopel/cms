<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Health\ExtensionHealthService;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_healthy_extension_as_ok(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'healthy-plugin',
            'name' => 'Healthy Plugin',
            'description' => 'Healthy fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/HealthyPlugin'),
            'manifest_path' => base_path('plugins/HealthyPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Healthy Plugin',
                'slug' => 'healthy-plugin',
                'description' => 'Healthy fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => 'Tests\\Fixtures\\Plugins\\BootableAlphaServiceProvider',
                'critical' => false,
                'requires' => [],
                'capabilities' => ['widgets'],
            ],
        ]);

        $report = app(ExtensionHealthService::class)->report()->toArray();

        $this->assertSame('ok', $report['overall_status']);
        $this->assertSame(1, $report['summary']['ok']);
        $this->assertEmpty($report['top_issues']);
    }

    public function test_it_reports_invalid_manifest_as_error(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => null,
            'name' => 'Broken Plugin',
            'description' => 'Broken fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => ['Field [slug] is required and must be a non-empty string.'],
            'manifest_warnings' => ['Field [requires] was ignored because it must be an array or string when present.'],
            'raw_manifest' => [],
        ]);

        $report = app(ExtensionHealthService::class)->report()->toArray();

        $this->assertSame('error', $report['overall_status']);
        $this->assertSame('error', $report['entries'][0]['status']);
    }

    public function test_it_reports_capability_warnings_and_inconsistencies(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'ops-theme',
            'name' => 'Ops Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/OpsTheme'),
            'manifest_path' => base_path('themes/OpsTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'manifest_warnings' => [
                'Capability [custom_bridge] is not recognized by the core and will be treated as custom metadata.',
            ],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Ops Theme',
                'slug' => 'ops-theme',
                'description' => 'Theme fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => ['providers', 'custom_bridge'],
            ],
        ]);

        $report = app(ExtensionHealthService::class)->report()->toArray();

        $this->assertSame('warning', $report['overall_status']);
        $this->assertSame('warning', $report['entries'][0]['status']);
        $this->assertNotEmpty($report['entries'][0]['issues']);
    }

    public function test_it_reports_missing_and_disabled_dependencies_and_critical_disabled(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'seo-kit',
            'name' => 'SEO Kit',
            'description' => 'Dependency fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/SeoKit'),
            'manifest_path' => base_path('plugins/SeoKit/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'SEO Kit',
                'slug' => 'seo-kit',
                'description' => 'Dependency fixture.',
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
            'slug' => 'analytics-hub',
            'name' => 'Analytics Hub',
            'description' => 'Dependent fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/AnalyticsHub'),
            'manifest_path' => base_path('plugins/AnalyticsHub/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Analytics Hub',
                'slug' => 'analytics-hub',
                'description' => 'Dependent fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => ['seo-kit', 'cms-base'],
                'capabilities' => [],
            ],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'critical-core-tools',
            'name' => 'Critical Core Tools',
            'description' => 'Critical fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CriticalCoreTools'),
            'manifest_path' => base_path('plugins/CriticalCoreTools/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Critical Core Tools',
                'slug' => 'critical-core-tools',
                'description' => 'Critical fixture.',
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

        $report = app(ExtensionHealthService::class)->report()->toArray();

        $this->assertSame('error', $report['overall_status']);
        $this->assertGreaterThanOrEqual(2, $report['summary']['error']);
        $this->assertNotEmpty($report['top_issues']);
    }
}
