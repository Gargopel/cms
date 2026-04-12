<?php

namespace App\Core\Extensions\Migrations;

class PluginMigrationStatus
{
    /**
     * @param  array<int, array{code: string, message: string}>  $blocks
     * @param  array<int, string>  $migrationFiles
     * @param  array<int, string>  $pendingMigrations
     */
    public function __construct(
        protected bool $eligible,
        protected bool $hasMigrationsDirectory,
        protected bool $hasMigrations,
        protected string $message,
        protected ?string $migrationsPath = null,
        protected array $migrationFiles = [],
        protected array $pendingMigrations = [],
        protected array $blocks = [],
    ) {
    }

    public function eligible(): bool
    {
        return $this->eligible;
    }

    public function hasMigrationsDirectory(): bool
    {
        return $this->hasMigrationsDirectory;
    }

    public function hasMigrations(): bool
    {
        return $this->hasMigrations;
    }

    public function hasPendingMigrations(): bool
    {
        return $this->pendingCount() > 0;
    }

    public function pendingCount(): int
    {
        return count($this->pendingMigrations);
    }

    public function message(): string
    {
        return $this->message;
    }

    public function migrationsPath(): ?string
    {
        return $this->migrationsPath;
    }

    /**
     * @return array<int, string>
     */
    public function migrationFiles(): array
    {
        return $this->migrationFiles;
    }

    /**
     * @return array<int, string>
     */
    public function pendingMigrations(): array
    {
        return $this->pendingMigrations;
    }

    /**
     * @return array<int, array{code: string, message: string}>
     */
    public function blocks(): array
    {
        return $this->blocks;
    }

    public function primaryBlockMessage(): ?string
    {
        return $this->blocks[0]['message'] ?? null;
    }

    public function canRun(): bool
    {
        return $this->eligible()
            && $this->hasMigrations()
            && $this->hasPendingMigrations()
            && $this->blocks() === [];
    }

    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible(),
            'can_run' => $this->canRun(),
            'has_migrations_directory' => $this->hasMigrationsDirectory(),
            'has_migrations' => $this->hasMigrations(),
            'has_pending_migrations' => $this->hasPendingMigrations(),
            'pending_count' => $this->pendingCount(),
            'message' => $this->message(),
            'migrations_path' => $this->migrationsPath(),
            'migration_files' => $this->migrationFiles(),
            'pending_migrations' => $this->pendingMigrations(),
            'blocks' => $this->blocks(),
        ];
    }
}
