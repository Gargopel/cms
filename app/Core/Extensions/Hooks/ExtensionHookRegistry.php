<?php

namespace App\Core\Extensions\Hooks;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Support\Facades\Schema;

class ExtensionHookRegistry implements AdminNavigationRegistry, AdminDashboardPanelRegistry
{
    /**
     * @var array<string, AdminNavigationItem>
     */
    protected array $navigationItems = [];

    /**
     * @var array<string, AdminDashboardPanel>
     */
    protected array $dashboardPanels = [];

    public function registerAdminNavigationItem(AdminNavigationItem $item): void
    {
        if (! $this->acceptsPluginContributions($item->pluginSlug())) {
            return;
        }

        $this->navigationItems[$item->uniqueKey()] = $item;
    }

    public function registerAdminDashboardPanel(AdminDashboardPanel $panel): void
    {
        if (! $this->acceptsPluginContributions($panel->pluginSlug())) {
            return;
        }

        $this->dashboardPanels[$panel->uniqueKey()] = $panel;
    }

    /**
     * @return array<int, AdminNavigationItem>
     */
    public function adminNavigationItems(): array
    {
        return array_values($this->navigationItems);
    }

    /**
     * @return array<int, AdminDashboardPanel>
     */
    public function adminDashboardPanels(): array
    {
        return array_values($this->dashboardPanels);
    }

    protected function acceptsPluginContributions(string $pluginSlug): bool
    {
        if (! Schema::hasTable('extension_records')) {
            return false;
        }

        $record = ExtensionRecord::query()
            ->where('type', ExtensionType::Plugin->value)
            ->where('slug', $pluginSlug)
            ->first();

        if (! $record instanceof ExtensionRecord) {
            return false;
        }

        return $record->discovery_status === ExtensionDiscoveryStatus::Valid
            && $record->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Installed
            && $record->operational_status === ExtensionOperationalStatus::Enabled;
    }
}
