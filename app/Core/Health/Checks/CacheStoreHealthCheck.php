<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheStoreHealthCheck implements SystemHealthCheck
{
    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache Store';
    }

    public function description(): string
    {
        return 'Valida se o driver de cache atual aceita escrita e leitura basicas.';
    }

    public function run(): HealthCheckResult
    {
        $cacheKey = 'platform.health.cache_check.'.md5((string) microtime(true));

        try {
            Cache::put($cacheKey, 'ok', 60);
            $resolved = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($resolved !== 'ok') {
                return new HealthCheckResult(
                    key: $this->key(),
                    label: $this->label(),
                    description: $this->description(),
                    status: HealthStatus::Warning,
                    message: 'O cache respondeu, mas o valor lido nao correspondeu ao esperado.',
                    meta: [
                        'store' => (string) config('cache.default'),
                    ],
                );
            }

            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                description: $this->description(),
                status: HealthStatus::Ok,
                message: 'O driver de cache respondeu corretamente ao check do core.',
                meta: [
                    'store' => (string) config('cache.default'),
                ],
            );
        } catch (Throwable) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                description: $this->description(),
                status: HealthStatus::Error,
                message: 'O core nao conseguiu confirmar a disponibilidade do cache configurado.',
                meta: [
                    'store' => (string) config('cache.default'),
                ],
            );
        }
    }
}
