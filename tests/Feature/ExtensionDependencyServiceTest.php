<?php

namespace Tests\Feature;

use App\Core\Extensions\Dependencies\ExtensionDependencyService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionDependencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_missing_and_disabled_required_extensions(): void
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
                'requires' => ['seo-kit', 'missing-addon'],
                'capabilities' => [],
            ],
        ]);

        $inspection = app(ExtensionDependencyService::class)->inspect($record)->toArray();

        $this->assertCount(2, $inspection['requirements']);
        $this->assertSame('disabled', $inspection['requirements'][0]['status']);
        $this->assertSame('missing', $inspection['requirements'][1]['status']);
        $this->assertSame('missing-addon', $inspection['missing_requirements'][0]['slug']);
        $this->assertSame('seo-kit', $inspection['disabled_requirements'][0]['slug']);
    }

    public function test_it_reports_active_reverse_dependents(): void
    {
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

        $inspection = app(ExtensionDependencyService::class)->inspect($dependency)->toArray();

        $this->assertCount(1, $inspection['active_dependents']);
        $this->assertSame('page-builder', $inspection['active_dependents'][0]['slug']);
    }
}
