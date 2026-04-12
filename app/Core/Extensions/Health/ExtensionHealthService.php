<?php

namespace App\Core\Extensions\Health;

use App\Core\Extensions\Capabilities\ExtensionCapabilityService;
use App\Core\Extensions\Dependencies\ExtensionDependencyService;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Health\Enums\HealthStatus;
use Illuminate\Support\Facades\Schema;

class ExtensionHealthService
{
    public function __construct(
        protected ExtensionCapabilityService $capabilities,
        protected ExtensionDependencyService $dependencies,
    ) {
    }

    public function report(): ExtensionHealthReport
    {
        if (! Schema::hasTable('extension_records')) {
            return new ExtensionHealthReport();
        }

        $entries = ExtensionRecord::query()
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('slug')
            ->get()
            ->map(fn (ExtensionRecord $record): ExtensionHealthEntry => $this->buildEntry($record))
            ->all();

        return new ExtensionHealthReport($entries);
    }

    protected function buildEntry(ExtensionRecord $record): ExtensionHealthEntry
    {
        $issues = [];
        $dependencyInspection = $this->dependencies->inspect($record);
        $capabilitySet = $this->capabilities->forExtension($record);
        $capabilityWarnings = $this->capabilities->warningsForExtension($record);

        if ($record->discovery_status === ExtensionDiscoveryStatus::Invalid) {
            $issues[] = $this->issue(
                status: HealthStatus::Error,
                code: 'manifest_invalid',
                message: $record->discovery_errors[0] ?? 'A extensao possui manifesto invalido ou inconsistente.',
            );
        }

        if ($record->discovery_status === ExtensionDiscoveryStatus::Incompatible) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: 'manifest_incompatible',
                message: $record->discovery_errors[0] ?? 'A extensao esta fora da compatibilidade atual do core.',
            );
        }

        if (! empty($record->manifest_warnings)) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: 'manifest_warnings',
                message: sprintf(
                    'O manifesto normalizado gerou %d warning(s) que merecem revisao operacional.',
                    count($record->manifest_warnings),
                ),
                meta: [
                    'warnings' => $record->manifest_warnings,
                ],
            );
        }

        if ($capabilityWarnings !== []) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: 'capability_warnings',
                message: sprintf(
                    'A extensao possui %d warning(s) de capabilities no manifesto normalizado.',
                    count($capabilityWarnings),
                ),
                meta: [
                    'warnings' => $capabilityWarnings,
                ],
            );
        }

        if ($dependencyInspection->hasMissingRequirements()) {
            $issues[] = $this->issue(
                status: $record->operational_status === ExtensionOperationalStatus::Enabled ? HealthStatus::Error : HealthStatus::Warning,
                code: 'missing_dependencies',
                message: sprintf(
                    'Dependencias ausentes: %s.',
                    implode(', ', array_column($dependencyInspection->missingRequirements(), 'slug')),
                ),
                meta: [
                    'requirements' => $dependencyInspection->missingRequirements(),
                ],
            );
        }

        if ($record->type === ExtensionType::Theme && $capabilitySet->has('providers')) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: 'theme_declares_providers_capability',
                message: 'O tema declara a capability [providers], mas temas nao entram no pipeline de providers do core.',
            );
        }

        if ($record->type === ExtensionType::Plugin && $capabilitySet->has('providers') && $record->declaredProvider() === null) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: 'providers_capability_without_provider',
                message: 'O plugin declara a capability [providers], mas nao possui provider resolvivel no manifesto normalizado.',
            );
        }

        if ($dependencyInspection->hasDisabledRequirements()) {
            $issues[] = $this->issue(
                status: $record->operational_status === ExtensionOperationalStatus::Enabled ? HealthStatus::Error : HealthStatus::Warning,
                code: 'disabled_dependencies',
                message: sprintf(
                    'Dependencias desabilitadas: %s.',
                    implode(', ', array_column($dependencyInspection->disabledRequirements(), 'slug')),
                ),
                meta: [
                    'requirements' => $dependencyInspection->disabledRequirements(),
                ],
            );
        }

        foreach ($dependencyInspection->warnings() as $warning) {
            $issues[] = $this->issue(
                status: HealthStatus::Warning,
                code: $warning['code'] ?? 'dependency_warning',
                message: $warning['message'] ?? 'Foi detectada uma inconsistencia de dependencia.',
                meta: $warning['context'] ?? [],
            );
        }

        if ($record->isCriticalForOperations() && $record->operational_status !== ExtensionOperationalStatus::Enabled) {
            $issues[] = $this->issue(
                status: HealthStatus::Error,
                code: 'critical_extension_disabled',
                message: 'Uma extensao marcada como critica nao esta habilitada operacionalmente.',
            );
        }

        if (
            $record->operational_status === ExtensionOperationalStatus::Enabled
            && $record->discovery_status !== ExtensionDiscoveryStatus::Valid
        ) {
            $issues[] = $this->issue(
                status: HealthStatus::Error,
                code: 'enabled_with_invalid_discovery',
                message: 'A extensao esta habilitada, mas seu discovery_status nao e valido.',
            );
        }

        $status = collect($issues)->contains(static fn (array $issue): bool => ($issue['status'] ?? null) === HealthStatus::Error->value)
            ? HealthStatus::Error
            : (collect($issues)->isNotEmpty() ? HealthStatus::Warning : HealthStatus::Ok);

        return new ExtensionHealthEntry(
            id: $record->getKey(),
            slug: $record->slug,
            name: $record->name ?? 'Unknown extension',
            type: $record->type?->value,
            status: $status,
            issues: $issues,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function issue(HealthStatus $status, string $code, string $message, array $meta = []): array
    {
        return [
            'status' => $status->value,
            'code' => $code,
            'message' => $message,
            'meta' => $meta,
        ];
    }
}
