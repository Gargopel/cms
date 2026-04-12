<?php

namespace App\Core\Extensions\Operations;

use App\Core\Extensions\Dependencies\ExtensionDependencyService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;

class ExtensionOperationEligibilityService
{
    public function __construct(
        protected ExtensionDependencyService $dependencies,
    ) {
    }

    public function evaluateEnable(ExtensionRecord $record): ExtensionOperationEligibility
    {
        $blocks = [];
        $warnings = [];
        $dependencyInspection = $this->dependencies->inspect($record);

        if (blank($record->slug)) {
            $blocks[] = $this->issue('missing_slug', 'A extensao nao possui slug persistido e nao pode ser operada com seguranca.');
        }

        if (blank($record->path) || blank($record->manifest_path)) {
            $blocks[] = $this->issue('registry_incomplete', 'O registro da extensao esta incompleto para operacao segura.');
        }

        if ($record->discovery_status !== ExtensionDiscoveryStatus::Valid) {
            $blocks[] = $this->issue(
                'invalid_discovery_status',
                sprintf(
                    'Extension cannot be enabled because its discovery status is [%s].',
                    $record->discovery_status?->value ?? 'unknown',
                ),
            );
        }

        if ($record->administrativeLifecycleStatus() !== ExtensionLifecycleStatus::Installed) {
            $blocks[] = $this->issue(
                'extension_not_installed',
                'A extensao precisa estar instalada no lifecycle administrativo antes de poder ser habilitada.',
            );
        }

        if ($record->operational_status === ExtensionOperationalStatus::Enabled) {
            $blocks[] = $this->issue('already_enabled', 'A extensao ja esta habilitada.');
        }

        foreach ($dependencyInspection->missingRequirements() as $requirement) {
            $blocks[] = $this->issue(
                'required_dependency_missing',
                sprintf(
                    'A extensao nao pode ser habilitada porque a dependencia [%s] nao foi encontrada no registro.',
                    $requirement['slug'],
                ),
            );
        }

        foreach ($dependencyInspection->disabledRequirements() as $requirement) {
            $blocks[] = $this->issue(
                'required_dependency_not_enabled',
                sprintf(
                    'A extensao nao pode ser habilitada porque a dependencia [%s] nao esta habilitada.',
                    $requirement['slug'],
                ),
            );
        }

        if ($record->type === ExtensionType::Theme) {
            $warnings[] = $this->issue(
                'theme_runtime_scope',
                'Temas podem ter estado operacional no registro, mas nao entram no pipeline de providers.',
            );
        }

        if ($record->type === ExtensionType::Plugin && $record->declaredProvider() === null) {
            $warnings[] = $this->issue(
                'plugin_without_provider',
                'O plugin pode ser habilitado no registro, mas nao participara do boot de providers sem um provider declarado.',
            );
        }

        $warnings = [...$warnings, ...$dependencyInspection->warnings()];

        return new ExtensionOperationEligibility(
            action: 'enable',
            allowed: $blocks === [],
            message: $blocks === []
                ? 'A extensao pode ser habilitada.'
                : 'A acao foi bloqueada por restricao operacional.',
            blocks: $blocks,
            warnings: $warnings,
        );
    }

    public function evaluateDisable(ExtensionRecord $record): ExtensionOperationEligibility
    {
        $blocks = [];
        $warnings = [];
        $dependencyInspection = $this->dependencies->inspect($record);

        if (blank($record->slug)) {
            $blocks[] = $this->issue('missing_slug', 'A extensao nao possui slug persistido e nao pode ser operada com seguranca.');
        }

        if ($record->operational_status === ExtensionOperationalStatus::Disabled) {
            $blocks[] = $this->issue('already_disabled', 'A extensao ja esta desabilitada.');
        }

        if ($record->isCriticalForOperations()) {
            $blocks[] = $this->issue(
                'critical_extension',
                'Esta extensao foi marcada como critica para a operacao do sistema e nao pode ser desabilitada nesta fase.',
            );
        }

        if ($dependencyInspection->hasActiveDependents()) {
            $dependentSummary = collect($dependencyInspection->activeDependents())
                ->take(3)
                ->map(static fn (array $dependent): string => $dependent['slug'] ?? 'unknown')
                ->implode(', ');

            $blocks[] = $this->issue(
                'active_dependents',
                sprintf(
                    'A extensao nao pode ser desabilitada porque existem dependentes ativos: %s.',
                    $dependentSummary,
                ),
            );
        }

        if ($record->declaredDependencies() !== []) {
            $warnings[] = $this->issue(
                'declared_dependencies_present',
                'A extensao declara dependencias e o core atualmente valida apenas dependencias diretas, sem cascata automatica ou versionamento.',
            );
        }

        return new ExtensionOperationEligibility(
            action: 'disable',
            allowed: $blocks === [],
            message: $blocks === []
                ? 'A extensao pode ser desabilitada.'
                : 'A acao foi bloqueada por restricao operacional.',
            blocks: $blocks,
            warnings: $warnings,
        );
    }

    public function evaluateInstall(ExtensionRecord $record): ExtensionOperationEligibility
    {
        $blocks = [];
        $warnings = [];
        $dependencyInspection = $this->dependencies->inspect($record);

        if (blank($record->slug)) {
            $blocks[] = $this->issue('missing_slug', 'A extensao nao possui slug persistido e nao pode ser instalada com seguranca.');
        }

        if (blank($record->path) || blank($record->manifest_path)) {
            $blocks[] = $this->issue('registry_incomplete', 'O registro da extensao esta incompleto para install seguro.');
        }

        if ($record->discovery_status !== ExtensionDiscoveryStatus::Valid) {
            $blocks[] = $this->issue(
                'invalid_discovery_status',
                sprintf(
                    'A extensao nao pode ser instalada porque seu discovery status e [%s].',
                    $record->discovery_status?->value ?? 'unknown',
                ),
            );
        }

        if ($record->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Installed) {
            $blocks[] = $this->issue('already_installed', 'A extensao ja esta instalada no lifecycle administrativo.');
        }

        if ($record->declaredDependencies() !== []) {
            $warnings[] = $this->issue(
                'declared_dependencies_present',
                'A extensao declara dependencias, mas o install desta fase apenas registra o lifecycle administrativo e nao instala dependencias automaticamente.',
            );
        }

        foreach ($dependencyInspection->missingRequirements() as $requirement) {
            $warnings[] = $this->issue(
                'required_dependency_missing_for_future_enable',
                sprintf(
                    'A dependencia [%s] ainda nao foi encontrada no registro. O install administrativo pode prosseguir, mas o enable continuara bloqueado ate essa dependencia existir.',
                    $requirement['slug'],
                ),
            );
        }

        foreach ($dependencyInspection->disabledRequirements() as $requirement) {
            $warnings[] = $this->issue(
                'required_dependency_not_enabled_for_future_enable',
                sprintf(
                    'A dependencia [%s] existe, mas nao esta habilitada. O install administrativo pode prosseguir, mas o enable continuara bloqueado ate essa dependencia ser habilitada.',
                    $requirement['slug'],
                ),
            );
        }

        $warnings = [...$warnings, ...$dependencyInspection->warnings()];

        return new ExtensionOperationEligibility(
            action: 'install',
            allowed: $blocks === [],
            message: $blocks === []
                ? 'A extensao pode ser instalada no registry administrativo.'
                : 'A acao foi bloqueada por restricao operacional.',
            blocks: $blocks,
            warnings: $warnings,
        );
    }

    public function evaluateRemove(ExtensionRecord $record): ExtensionOperationEligibility
    {
        $blocks = [];
        $warnings = [];
        $dependencyInspection = $this->dependencies->inspect($record);

        if (blank($record->slug)) {
            $blocks[] = $this->issue('missing_slug', 'A extensao nao possui slug persistido e nao pode ser removida com seguranca.');
        }

        if ($record->administrativeLifecycleStatus() === ExtensionLifecycleStatus::Removed) {
            $blocks[] = $this->issue('already_removed', 'A extensao ja foi removida do lifecycle administrativo.');
        }

        if ($record->operational_status === ExtensionOperationalStatus::Enabled) {
            $blocks[] = $this->issue('enabled_extension', 'A extensao nao pode ser removida enquanto estiver habilitada operacionalmente.');
        }

        if ($record->isCriticalForOperations()) {
            $blocks[] = $this->issue(
                'critical_extension',
                'Esta extensao foi marcada como critica para a operacao do sistema e nao pode ser removida nesta fase.',
            );
        }

        if ($dependencyInspection->hasActiveDependents()) {
            $dependentSummary = collect($dependencyInspection->activeDependents())
                ->take(3)
                ->map(static fn (array $dependent): string => $dependent['slug'] ?? 'unknown')
                ->implode(', ');

            $blocks[] = $this->issue(
                'active_dependents',
                sprintf(
                    'A extensao nao pode ser removida porque existem dependentes ativos: %s.',
                    $dependentSummary,
                ),
            );
        }

        if ($record->declaredDependencies() !== []) {
            $warnings[] = $this->issue(
                'declared_dependencies_present',
                'A extensao declara dependencias, mas o remove desta fase apenas desregistra o lifecycle administrativo e nao executa cadeias de remocao.',
            );
        }

        $warnings = [...$warnings, ...$dependencyInspection->warnings()];

        return new ExtensionOperationEligibility(
            action: 'remove',
            allowed: $blocks === [],
            message: $blocks === []
                ? 'A extensao pode ser removida do lifecycle administrativo.'
                : 'A acao foi bloqueada por restricao operacional.',
            blocks: $blocks,
            warnings: $warnings,
        );
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
}
