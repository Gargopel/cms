<?php

namespace App\Core\Extensions\Boot;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Throwable;

class PluginBootstrapReportStore
{
    public function __construct(
        protected CacheRepository $cache,
    ) {
    }

    public function remember(PluginBootReport $report): void
    {
        try {
            $this->cache->forever($this->cacheKey(), [
                'stored_at' => Carbon::now()->toISOString(),
                'report' => $report->toArray(),
            ]);
        } catch (Throwable) {
            // Keep bootstrap resilient even if cache storage is unavailable.
        }
    }

    public function last(): ?array
    {
        try {
            $payload = $this->cache->get($this->cacheKey());
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    protected function cacheKey(): string
    {
        return (string) config('platform.observability.plugin_bootstrap_report_cache_key', 'platform.extensions.bootstrap.last_report');
    }
}
