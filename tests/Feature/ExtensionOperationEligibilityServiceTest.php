<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Operations\ExtensionOperationEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionOperationEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_enable_evaluation_allows_valid_extension_and_reports_warnings_when_applicable(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Theme,
            'slug' => 'studio-theme',
            'name' => 'Studio Theme',
            'description' => 'Theme fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('themes/StudioTheme'),
            'manifest_path' => base_path('themes/StudioTheme/theme.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'raw_manifest' => ['critical' => true, 'requires' => ['raw-only']],
            'normalized_manifest' => [
                'type' => 'theme',
                'name' => 'Studio Theme',
                'slug' => 'studio-theme',
                'description' => 'Theme fixture.',
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateEnable($record)
            ->toArray();

        $this->assertTrue($evaluation['allowed']);
        $this->assertNotEmpty($evaluation['warnings']);
        $this->assertSame('theme_runtime_scope', $evaluation['warnings'][0]['code']);
    }

    public function test_enable_evaluation_blocks_when_required_extension_is_missing(): void
    {
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateEnable($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('required_dependency_missing', $evaluation['blocks'][0]['code']);
    }

    public function test_enable_evaluation_blocks_when_required_extension_is_disabled_using_legacy_fallback(): void
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
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'raw_manifest' => [],
        ]);

        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'legacy-analytics',
            'name' => 'Legacy Analytics',
            'description' => 'Legacy fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/LegacyAnalytics'),
            'manifest_path' => base_path('plugins/LegacyAnalytics/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Discovered,
            'discovery_errors' => [],
            'raw_manifest' => ['requires' => ['seo-kit']],
        ]);

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateEnable($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('required_dependency_not_enabled', $evaluation['blocks'][0]['code']);
    }

    public function test_disable_evaluation_blocks_critical_extension_from_normalized_manifest(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'critical-tools',
            'name' => 'Critical Tools',
            'description' => 'Critical fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CriticalTools'),
            'manifest_path' => base_path('plugins/CriticalTools/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'raw_manifest' => ['critical' => false],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Critical Tools',
                'slug' => 'critical-tools',
                'description' => 'Critical fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => true,
                'requires' => ['cms-base'],
                'capabilities' => [],
            ],
        ]);

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateDisable($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('critical_extension', $evaluation['blocks'][0]['code']);
        $this->assertSame('declared_dependencies_present', $evaluation['warnings'][0]['code']);
    }

    public function test_disable_evaluation_blocks_when_enabled_dependents_exist(): void
    {
        $record = ExtensionRecord::query()->create([
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateDisable($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('active_dependents', $evaluation['blocks'][0]['code']);
    }

    public function test_enable_evaluation_blocks_when_extension_is_not_installed_in_admin_lifecycle(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'media-tools',
            'name' => 'Media Tools',
            'description' => 'Plugin fixture.',
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
                'description' => 'Plugin fixture.',
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateEnable($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('extension_not_installed', $evaluation['blocks'][0]['code']);
    }

    public function test_install_evaluation_allows_valid_discovered_extension_and_reports_dependency_warnings(): void
    {
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
            'lifecycle_status' => ExtensionLifecycleStatus::Discovered,
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateInstall($record)
            ->toArray();

        $this->assertTrue($evaluation['allowed']);
        $this->assertNotEmpty($evaluation['warnings']);
        $this->assertSame('declared_dependencies_present', $evaluation['warnings'][0]['code']);
    }

    public function test_remove_evaluation_blocks_when_extension_is_critical(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'critical-tools',
            'name' => 'Critical Tools',
            'description' => 'Critical fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/CriticalTools'),
            'manifest_path' => base_path('plugins/CriticalTools/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Critical Tools',
                'slug' => 'critical-tools',
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

        $evaluation = app(ExtensionOperationEligibilityService::class)
            ->evaluateRemove($record)
            ->toArray();

        $this->assertFalse($evaluation['allowed']);
        $this->assertSame('critical_extension', $evaluation['blocks'][0]['code']);
    }
}
