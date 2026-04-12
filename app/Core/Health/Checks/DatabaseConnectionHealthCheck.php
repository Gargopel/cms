<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseConnectionHealthCheck implements SystemHealthCheck
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'Database Connection';
    }

    public function description(): string
    {
        return 'Valida se a conexao de banco configurada pelo core responde a uma consulta simples.';
    }

    public function run(): HealthCheckResult
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1 as health_check');

            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                description: $this->description(),
                status: HealthStatus::Ok,
                message: 'A conexao de banco respondeu corretamente ao check do core.',
                meta: [
                    'connection' => (string) config('database.default'),
                ],
            );
        } catch (Throwable) {
            return new HealthCheckResult(
                key: $this->key(),
                label: $this->label(),
                description: $this->description(),
                status: HealthStatus::Error,
                message: 'O core nao conseguiu confirmar a conexao com o banco configurado.',
                meta: [
                    'connection' => (string) config('database.default'),
                ],
            );
        }
    }
}
