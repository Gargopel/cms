<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;
use App\Core\Install\InstallationState;

class InstallationHealthCheck implements SystemHealthCheck
{
    public function __construct(
        protected InstallationState $installationState,
    ) {
    }

    public function key(): string
    {
        return 'installation';
    }

    public function label(): string
    {
        return 'Application Installed';
    }

    public function description(): string
    {
        return 'Verifica se a instalacao ja foi concluida e marcada pelo core.';
    }

    public function run(): HealthCheckResult
    {
        $installed = $this->installationState->isInstalled();

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            description: $this->description(),
            status: $installed ? HealthStatus::Ok : HealthStatus::Error,
            message: $installed
                ? 'A instalacao foi concluida e o marcador do core esta presente.'
                : 'A instalacao ainda nao esta marcada como concluida.',
            meta: [
                'marker_present' => $installed,
            ],
        );
    }
}
