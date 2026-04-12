<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;
use Illuminate\Support\Facades\Schema;

class ExtensionsRegistryHealthCheck implements SystemHealthCheck
{
    public function key(): string
    {
        return 'extensions_registry';
    }

    public function label(): string
    {
        return 'Extensions Registry';
    }

    public function description(): string
    {
        return 'Resume o estado das extensoes registradas para sinalizar risco operacional basico.';
    }

    public function run(): HealthCheckResult
    {
        if (! Schema::hasTable('extension_records')) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                description: $this->description(),
                status: HealthStatus::Warning,
                message: 'O registro de extensoes ainda nao esta disponivel nesta instalacao.',
                meta: [
                    'total' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                    'incompatible' => 0,
                ],
            );
        }

        $total = ExtensionRecord::query()->count();
        $valid = ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Valid)->count();
        $invalid = ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Invalid)->count();
        $incompatible = ExtensionRecord::query()->where('discovery_status', ExtensionDiscoveryStatus::Incompatible)->count();

        $status = ($invalid + $incompatible) > 0 ? HealthStatus::Warning : HealthStatus::Ok;
        $message = ($invalid + $incompatible) > 0
            ? 'Existem extensoes invalidas ou incompativeis exigindo atencao operacional.'
            : 'O registro de extensoes nao aponta problemas estruturais conhecidos neste momento.';

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            description: $this->description(),
            status: $status,
            message: $message,
            meta: [
                'total' => $total,
                'valid' => $valid,
                'invalid' => $invalid,
                'incompatible' => $incompatible,
            ],
        );
    }
}
