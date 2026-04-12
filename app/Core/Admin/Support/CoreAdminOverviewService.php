<?php

namespace App\Core\Admin\Support;

use App\Core\Extensions\Boot\PluginBootstrapReportStore;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Health\SystemHealthService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class CoreAdminOverviewService
{
    public function __construct(
        protected PluginBootstrapReportStore $bootstrapReportStore,
        protected SystemHealthService $health,
    ) {
    }

    public function dashboardMetrics(): array
    {
        if (! $this->hasRegistryTable()) {
            return [
                'extensions_total' => 0,
                'plugins_total' => 0,
                'themes_total' => 0,
                'enabled_total' => 0,
                'invalid_total' => 0,
                'incompatible_total' => 0,
            ];
        }

        return [
            'extensions_total' => ExtensionRecord::query()->count(),
            'plugins_total' => ExtensionRecord::query()->where('type', ExtensionType::Plugin)->count(),
            'themes_total' => ExtensionRecord::query()->where('type', ExtensionType::Theme)->count(),
            'enabled_total' => ExtensionRecord::query()->where('operational_status', ExtensionOperationalStatus::Enabled)->count(),
            'invalid_total' => ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Invalid)->count(),
            'incompatible_total' => ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Incompatible)->count(),
        ];
    }

    public function extensionStatusSummary(): array
    {
        if (! $this->hasRegistryTable()) {
            return ['discovery' => [], 'lifecycle' => [], 'operational' => []];
        }

        $lifecycleCounts = ExtensionRecord::query()
            ->select(['id', 'lifecycle_status', 'operational_status'])
            ->get()
            ->countBy(static fn (ExtensionRecord $record): string => $record->administrativeLifecycleStatus()->value);

        return [
            'discovery' => [
                ['label' => 'Valid', 'value' => ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Valid)->count()],
                ['label' => 'Invalid', 'value' => ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Invalid)->count()],
                ['label' => 'Incompatible', 'value' => ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Incompatible)->count()],
            ],
            'lifecycle' => [
                ['label' => 'Discovered', 'value' => $lifecycleCounts[ExtensionLifecycleStatus::Discovered->value] ?? 0],
                ['label' => 'Installed', 'value' => $lifecycleCounts[ExtensionLifecycleStatus::Installed->value] ?? 0],
                ['label' => 'Removed', 'value' => $lifecycleCounts[ExtensionLifecycleStatus::Removed->value] ?? 0],
            ],
            'operational' => [
                ['label' => 'Discovered', 'value' => ExtensionRecord::query()->where('operational_status', ExtensionOperationalStatus::Discovered)->count()],
                ['label' => 'Installed', 'value' => ExtensionRecord::query()->where('operational_status', ExtensionOperationalStatus::Installed)->count()],
                ['label' => 'Enabled', 'value' => ExtensionRecord::query()->where('operational_status', ExtensionOperationalStatus::Enabled)->count()],
                ['label' => 'Disabled', 'value' => ExtensionRecord::query()->where('operational_status', ExtensionOperationalStatus::Disabled)->count()],
            ],
        ];
    }

    public function paginatedExtensions(int $perPage = 20): LengthAwarePaginator
    {
        if (! $this->hasRegistryTable()) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return ExtensionRecord::query()
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('slug')
            ->paginate($perPage);
    }

    public function recentExtensions(int $limit = 6): array
    {
        if (! $this->hasRegistryTable()) {
            return [];
        }

        return ExtensionRecord::query()
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(static fn (ExtensionRecord $record): array => $record->toRegistryArray())
            ->all();
    }

    public function bootstrapReport(): ?array
    {
        return $this->bootstrapReportStore->last();
    }

    public function systemStatus(): array
    {
        return [
            ['label' => 'Core Version', 'value' => (string) config('platform.core.version', '0.1.0')],
            ['label' => 'Laravel', 'value' => app()->version()],
            ['label' => 'PHP', 'value' => PHP_VERSION],
            ['label' => 'Environment', 'value' => app()->environment()],
            ['label' => 'Debug', 'value' => config('app.debug') ? 'Enabled' : 'Disabled'],
            ['label' => 'Cache Store', 'value' => (string) config('cache.default')],
            ['label' => 'Queue Connection', 'value' => (string) config('queue.default')],
            ['label' => 'Session Driver', 'value' => (string) config('session.driver')],
            ['label' => 'Timezone', 'value' => (string) config('app.timezone')],
            ['label' => 'Extensions Registry', 'value' => $this->hasRegistryTable() ? 'Ready' : 'Pending migration'],
        ];
    }

    public function healthSummary(): array
    {
        return $this->health->run()->toArray();
    }

    protected function hasRegistryTable(): bool
    {
        return Schema::hasTable('extension_records');
    }
}
