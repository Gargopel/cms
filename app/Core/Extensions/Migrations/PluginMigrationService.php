<?php

namespace App\Core\Extensions\Migrations;

use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Throwable;

class PluginMigrationService
{
    public function __construct(
        protected Application $app,
        protected ConsoleKernel $artisan,
    ) {
    }

    public function statusFor(ExtensionRecord $record): PluginMigrationStatus
    {
        $blocks = [];

        if ($record->type !== ExtensionType::Plugin) {
            $blocks[] = $this->issue('not_a_plugin', 'A extensao selecionada nao e um plugin e nao participa deste fluxo de migrations.');
        }

        if ($record->discovery_status !== ExtensionDiscoveryStatus::Valid) {
            $blocks[] = $this->issue(
                'invalid_discovery_status',
                sprintf(
                    'As migrations nao podem ser executadas porque o discovery status do plugin e [%s].',
                    $record->discovery_status?->value ?? 'unknown',
                ),
            );
        }

        if ($record->administrativeLifecycleStatus() !== ExtensionLifecycleStatus::Installed) {
            $blocks[] = $this->issue(
                'plugin_not_installed',
                'As migrations do plugin exigem que a extensao esteja instalada no lifecycle administrativo.',
            );
        }

        if (! $this->migrationRepositoryExists()) {
            $blocks[] = $this->issue(
                'migration_repository_missing',
                'A tabela base de migrations do Laravel nao esta disponivel nesta instalacao.',
            );
        }

        $migrationsPath = $this->migrationsPathFor($record);
        $hasDirectory = is_dir($migrationsPath);
        $migrationFiles = $hasDirectory ? $this->migrationFilesIn($migrationsPath) : [];
        $pendingMigrations = $this->pendingMigrationsFor($migrationFiles);

        if ($blocks !== []) {
            return new PluginMigrationStatus(
                eligible: false,
                hasMigrationsDirectory: $hasDirectory,
                hasMigrations: $migrationFiles !== [],
                message: 'A operacao de migrations do plugin foi bloqueada por restricao operacional.',
                migrationsPath: $migrationsPath,
                migrationFiles: $migrationFiles,
                pendingMigrations: $pendingMigrations,
                blocks: $blocks,
            );
        }

        if (! $hasDirectory) {
            return new PluginMigrationStatus(
                eligible: true,
                hasMigrationsDirectory: false,
                hasMigrations: false,
                message: 'O plugin nao possui diretorio de migrations declarado nesta fase.',
                migrationsPath: $migrationsPath,
            );
        }

        if ($migrationFiles === []) {
            return new PluginMigrationStatus(
                eligible: true,
                hasMigrationsDirectory: true,
                hasMigrations: false,
                message: 'O plugin nao possui arquivos de migration pendentes ou declarados.',
                migrationsPath: $migrationsPath,
            );
        }

        if ($pendingMigrations === []) {
            return new PluginMigrationStatus(
                eligible: true,
                hasMigrationsDirectory: true,
                hasMigrations: true,
                message: 'Nao ha migrations pendentes para este plugin.',
                migrationsPath: $migrationsPath,
                migrationFiles: $migrationFiles,
                pendingMigrations: [],
            );
        }

        return new PluginMigrationStatus(
            eligible: true,
            hasMigrationsDirectory: true,
            hasMigrations: true,
            message: 'Existem migrations pendentes para este plugin.',
            migrationsPath: $migrationsPath,
            migrationFiles: $migrationFiles,
            pendingMigrations: $pendingMigrations,
        );
    }

    public function runPendingFor(ExtensionRecord $record): PluginMigrationRunResult
    {
        $status = $this->statusFor($record);

        if (! $status->canRun()) {
            return new PluginMigrationRunResult(
                success: false,
                changed: false,
                message: $status->primaryBlockMessage() ?? $status->message(),
                record: $record,
                status: $status,
            );
        }

        try {
            $exitCode = $this->artisan->call('migrate', [
                '--path' => $status->migrationsPath(),
                '--realpath' => true,
                '--force' => true,
            ]);
        } catch (Throwable $exception) {
            return new PluginMigrationRunResult(
                success: false,
                changed: false,
                message: $exception->getMessage(),
                record: $record,
                status: $status,
            );
        }

        $refreshedStatus = $this->statusFor($record->fresh() ?? $record);

        if ($exitCode !== 0) {
            return new PluginMigrationRunResult(
                success: false,
                changed: false,
                message: 'A execucao das migrations do plugin falhou.',
                record: $record->fresh() ?? $record,
                status: $refreshedStatus,
                exitCode: $exitCode,
            );
        }

        return new PluginMigrationRunResult(
            success: true,
            changed: true,
            message: 'Plugin migrations executed successfully.',
            record: $record->fresh() ?? $record,
            status: $refreshedStatus,
            exitCode: $exitCode,
        );
    }

    protected function migrationsPathFor(ExtensionRecord $record): string
    {
        return rtrim((string) $record->path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
    }

    /**
     * @return array<int, string>
     */
    protected function migrationFilesIn(string $migrationsPath): array
    {
        $files = glob($migrationsPath.DIRECTORY_SEPARATOR.'*.php') ?: [];
        sort($files);

        return array_values($files);
    }

    /**
     * @param  array<int, string>  $migrationFiles
     * @return array<int, string>
     */
    protected function pendingMigrationsFor(array $migrationFiles): array
    {
        if ($migrationFiles === [] || ! $this->migrationRepositoryExists()) {
            return [];
        }

        $ran = collect($this->migrationRepository()->getRan())
            ->map(static fn (string $migration): string => strtolower(trim($migration)))
            ->all();

        return array_values(array_filter(
            array_map(static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME), $migrationFiles),
            static fn (string $migration): bool => ! in_array(strtolower($migration), $ran, true),
        ));
    }

    /**
     * @return array{code: string, message: string}
     */
    protected function issue(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }

    protected function migrationRepositoryExists(): bool
    {
        return $this->migrationRepository()->repositoryExists();
    }

    protected function migrationRepository(): object
    {
        /** @var object $repository */
        $repository = $this->app->make('migration.repository');

        return $repository;
    }
}
