<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;

class AppKeyHealthCheck implements SystemHealthCheck
{
    public function key(): string
    {
        return 'app_key';
    }

    public function label(): string
    {
        return 'Application Key';
    }

    public function description(): string
    {
        return 'Verifica se a aplicacao possui APP_KEY configurada sem expor o valor.';
    }

    public function run(): HealthCheckResult
    {
        $configured = trim((string) config('app.key')) !== '';

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            description: $this->description(),
            status: $configured ? HealthStatus::Ok : HealthStatus::Error,
            message: $configured
                ? 'A APP_KEY esta configurada.'
                : 'A APP_KEY nao esta configurada.',
            meta: [
                'configured' => $configured,
            ],
        );
    }
}
