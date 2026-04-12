<?php

namespace Tests\Feature;

use App\Core\Extensions\Capabilities\ExtensionCapabilityService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionCapabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_stable_fallback_when_capabilities_are_absent(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'plain-plugin',
            'name' => 'Plain Plugin',
            'description' => 'Plain fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/PlainPlugin'),
            'manifest_path' => base_path('plugins/PlainPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Plain Plugin',
                'slug' => 'plain-plugin',
                'description' => 'Plain fixture.',
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

        $set = app(ExtensionCapabilityService::class)->forExtension($record)->toArray();

        $this->assertSame([], $set['all']);
        $this->assertSame([], $set['recognized']);
        $this->assertSame([], $set['custom']);
    }

    public function test_it_classifies_recognized_and_custom_capabilities_and_supports_queries(): void
    {
        $record = ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'ops-plugin',
            'name' => 'Ops Plugin',
            'description' => 'Capability fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/OpsPlugin'),
            'manifest_path' => base_path('plugins/OpsPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => [],
            'normalized_manifest' => [
                'type' => 'plugin',
                'name' => 'Ops Plugin',
                'slug' => 'ops-plugin',
                'description' => 'Capability fixture.',
                'version' => '0.1.0',
                'author' => 'Tests',
                'vendor' => null,
                'core' => ['min' => '0.1.0'],
                'provider' => null,
                'critical' => false,
                'requires' => [],
                'capabilities' => ['widgets', 'health_checks', 'custom_bridge'],
            ],
            'manifest_warnings' => [
                'Capability [custom_bridge] is not recognized by the core and will be treated as custom metadata.',
            ],
        ]);

        $service = app(ExtensionCapabilityService::class);
        $set = $service->forExtension($record)->toArray();

        $this->assertSame(['widgets', 'health_checks'], $set['recognized']);
        $this->assertSame(['custom_bridge'], $set['custom']);
        $this->assertTrue($service->isRecognized('widgets'));
        $this->assertFalse($service->isRecognized('custom_bridge'));
        $this->assertSame('Health Checks', $service->label('health_checks'));
        $this->assertCount(1, $service->extensionsDeclaring('widgets'));
        $this->assertNotEmpty($service->warningsForExtension($record));
    }
}
