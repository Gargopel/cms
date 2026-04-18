<?php

namespace Tests\Feature;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use App\Core\Extensions\Hooks\ExtensionHookRegistry;
use App\Core\Extensions\Hooks\ThemeSlotBlock;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionHookRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_contributions_from_valid_installed_enabled_plugin(): void
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
        ]);

        $registry = app(ExtensionHookRegistry::class);

        $registry->registerAdminNavigationItem(new AdminNavigationItem(
            pluginSlug: 'reporting-suite',
            key: 'reports',
            label: 'Reports',
            description: 'Plugin navigation item.',
            href: '/admin/extensions',
        ));
        $registry->registerAdminDashboardPanel(new AdminDashboardPanel(
            pluginSlug: 'reporting-suite',
            key: 'snapshot',
            title: 'Reporting Snapshot',
            description: 'Plugin dashboard panel.',
        ));
        $registry->registerThemeSlotBlock(new ThemeSlotBlock(
            pluginSlug: 'reporting-suite',
            key: 'home-cta',
            slot: 'footer_cta',
            view: 'front.home',
            themeView: 'plugins.reporting-suite.slots.home-cta',
            dataResolver: fn (array $context): array => ['title' => 'Reporting'],
        ));

        $this->assertCount(1, $registry->adminNavigationItems());
        $this->assertCount(1, $registry->adminDashboardPanels());
        $this->assertCount(1, $registry->themeSlotBlocks('footer_cta'));
    }

    public function test_it_rejects_contributions_from_plugins_outside_operational_hook_window(): void
    {
        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'disabled-plugin',
            'name' => 'Disabled Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/DisabledPlugin'),
            'manifest_path' => base_path('plugins/DisabledPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'removed-plugin',
            'name' => 'Removed Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/RemovedPlugin'),
            'manifest_path' => base_path('plugins/RemovedPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Valid,
            'lifecycle_status' => ExtensionLifecycleStatus::Removed,
            'operational_status' => ExtensionOperationalStatus::Disabled,
            'discovery_errors' => [],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'broken-plugin',
            'name' => 'Broken Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/BrokenPlugin'),
            'manifest_path' => base_path('plugins/BrokenPlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Invalid,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => ['Invalid manifest.'],
        ]);

        ExtensionRecord::query()->create([
            'type' => ExtensionType::Plugin,
            'slug' => 'future-plugin',
            'name' => 'Future Plugin',
            'description' => 'Plugin fixture.',
            'author' => 'Tests',
            'detected_version' => '0.1.0',
            'path' => base_path('plugins/FuturePlugin'),
            'manifest_path' => base_path('plugins/FuturePlugin/plugin.json'),
            'discovery_status' => ExtensionDiscoveryStatus::Incompatible,
            'lifecycle_status' => ExtensionLifecycleStatus::Installed,
            'operational_status' => ExtensionOperationalStatus::Enabled,
            'discovery_errors' => ['Core version mismatch.'],
        ]);

        $registry = app(ExtensionHookRegistry::class);

        foreach (['disabled-plugin', 'removed-plugin', 'broken-plugin', 'future-plugin'] as $pluginSlug) {
            $registry->registerAdminNavigationItem(new AdminNavigationItem(
                pluginSlug: $pluginSlug,
                key: 'reports',
                label: 'Reports',
                description: 'Plugin navigation item.',
                href: '/admin/extensions',
            ));

            $registry->registerAdminDashboardPanel(new AdminDashboardPanel(
                pluginSlug: $pluginSlug,
                key: 'snapshot',
                title: 'Snapshot',
                description: 'Plugin dashboard panel.',
            ));

            $registry->registerThemeSlotBlock(new ThemeSlotBlock(
                pluginSlug: $pluginSlug,
                key: 'home-cta',
                slot: 'footer_cta',
                view: 'front.home',
            ));
        }

        $this->assertCount(0, $registry->adminNavigationItems());
        $this->assertCount(0, $registry->adminDashboardPanels());
        $this->assertCount(0, $registry->themeSlotBlocks('footer_cta'));
    }
}
