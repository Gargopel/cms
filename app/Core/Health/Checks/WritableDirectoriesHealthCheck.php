<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Health\Enums\HealthStatus;
use App\Core\Health\HealthCheckResult;
use Illuminate\Filesystem\Filesystem;

class WritableDirectoriesHealthCheck implements SystemHealthCheck
{
    public function __construct(
        protected Filesystem $files,
    ) {
    }

    public function key(): string
    {
        return 'writable_directories';
    }

    public function label(): string
    {
        return 'Writable Directories';
    }

    public function description(): string
    {
        return 'Confirma se diretorios criticos para cache, bootstrap e runtime permanecem gravaveis.';
    }

    public function run(): HealthCheckResult
    {
        $directories = [
            'storage' => storage_path(),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ];

        $nonWritable = collect($directories)
            ->filter(fn (string $path): bool => ! $this->files->isWritable($path))
            ->keys()
            ->values()
            ->all();

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            description: $this->description(),
            status: $nonWritable === [] ? HealthStatus::Ok : HealthStatus::Error,
            message: $nonWritable === []
                ? 'Os diretorios criticos avaliados pelo core estao gravaveis.'
                : 'Um ou mais diretorios criticos nao estao gravaveis para operacao segura.',
            meta: [
                'checked' => array_keys($directories),
                'non_writable' => $nonWritable,
            ],
        );
    }
}
