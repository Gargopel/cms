<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Support\CoreAdminOverviewService;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Extensions\Capabilities\ExtensionCapabilityService;
use App\Core\Extensions\Dependencies\ExtensionDependencyService;
use App\Core\Extensions\Health\ExtensionHealthService;
use App\Core\Extensions\Migrations\PluginMigrationService;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Operations\ExtensionOperationEligibilityService;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminExtensionsController extends Controller
{
    public function index(
        CoreAdminOverviewService $overview,
        ExtensionOperationEligibilityService $eligibility,
        ExtensionDependencyService $dependencies,
        ExtensionCapabilityService $capabilities,
        ExtensionHealthService $health,
        PluginMigrationService $pluginMigrations,
    ): View
    {
        $extensions = $overview->paginatedExtensions();
        $healthReport = $health->report();
        $extensionStates = [];

        foreach ($extensions as $extension) {
            $installState = $eligibility->evaluateInstall($extension)->toArray();
            $enableState = $eligibility->evaluateEnable($extension)->toArray();
            $disableState = $eligibility->evaluateDisable($extension)->toArray();
            $removeState = $eligibility->evaluateRemove($extension)->toArray();
            $dependencyState = $dependencies->inspect($extension)->toArray();
            $capabilityState = $capabilities->forExtension($extension)->toArray();
            $healthEntry = $healthReport->entryFor($extension->getKey())?->toArray();
            $migrationState = $pluginMigrations->statusFor($extension)->toArray();
            $isEnabled = $extension->operational_status?->value === 'enabled';
            $isInstalled = $extension->isAdministrativelyInstalled();
            $isRemoved = $extension->isAdministrativelyRemoved();
            $lifecycleStatus = $extension->administrativeLifecycleStatus()->value;
            $manifest = $extension->normalizedManifestSnapshot();
            $primaryAction = ! $isInstalled ? $installState : ($isEnabled ? $disableState : $enableState);
            $canInstallAction = ! $isInstalled && ($installState['allowed'] ?? false);
            $canEnableAction = $isInstalled && ! $isEnabled && ($enableState['allowed'] ?? false);
            $canDisableAction = $isEnabled && ($disableState['allowed'] ?? false);
            $canRemoveAction = $removeState['allowed'] ?? false;
            $canRunMigrationsAction = $migrationState['can_run'] ?? false;

            $extensionStates[$extension->getKey()] = [
                'install' => $installState,
                'enable' => $enableState,
                'disable' => $disableState,
                'remove' => $removeState,
                'is_enabled' => $isEnabled,
                'is_installed' => $isInstalled,
                'is_removed' => $isRemoved,
                'lifecycle_status' => $lifecycleStatus,
                'primary_action' => $primaryAction,
                'can_install_action' => $canInstallAction,
                'can_enable_action' => $canEnableAction,
                'can_disable_action' => $canDisableAction,
                'can_remove_action' => $canRemoveAction,
                'can_run_migrations_action' => $canRunMigrationsAction,
                'has_any_action' => $canInstallAction || $canEnableAction || $canDisableAction || $canRemoveAction || $canRunMigrationsAction,
                'dependencies' => $dependencyState,
                'capabilities' => [
                    ...$capabilityState,
                    'warnings' => $capabilities->warningsForExtension($extension),
                    'recognized_labels' => array_values(array_map(
                        fn (string $capability): string => $capabilities->label($capability),
                        $capabilityState['recognized'],
                    )),
                ],
                'health' => $healthEntry,
                'migrations' => $migrationState,
                'manifest' => [
                    'name' => $manifest['name'] ?? $extension->name ?? 'Unknown extension',
                    'slug' => $manifest['slug'] ?? $extension->slug ?? 'no-slug',
                    'vendor' => $manifest['vendor'] ?? null,
                    'provider' => $manifest['provider'] ?? null,
                    'critical' => (bool) ($manifest['critical'] ?? false),
                    'requires' => is_array($manifest['requires'] ?? null) ? $manifest['requires'] : [],
                    'capabilities' => is_array($manifest['capabilities'] ?? null) ? $manifest['capabilities'] : [],
                    'is_normalized' => $manifest !== null,
                    'has_warnings' => ! empty($extension->manifest_warnings),
                ],
            ];
        }

        return view('admin.extensions.index', [
            'pageTitle' => 'Registered Extensions',
            'pageSubtitle' => 'Leitura operacional do registro persistido de plugins e temas sincronizados, com lifecycle administrativo e acoes seguras.',
            'extensions' => $extensions,
            'statusSummary' => $overview->extensionStatusSummary(),
            'healthSummary' => $healthReport->toArray(),
            'bootstrapReport' => $overview->bootstrapReport(),
            'canManageExtensions' => request()->user()?->can('manage_extensions') ?? false,
            'extensionStates' => $extensionStates,
        ]);
    }

    public function runMigrations(
        Request $request,
        ExtensionRecord $extension,
        PluginMigrationService $pluginMigrations,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $status = $pluginMigrations->statusFor($extension);

        if (! $status->canRun()) {
            $auditLogger->log(
                action: 'admin.extensions.migrations_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $status->primaryBlockMessage() ?? $status->message(),
                metadata: [
                    'type' => $extension->type?->value,
                    'slug' => $extension->slug,
                    'pending_count' => $status->pendingCount(),
                    'migrations_path' => $status->migrationsPath(),
                    'blocks' => $status->blocks(),
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.extensions.index')
                ->withErrors([
                    'extensions' => $status->primaryBlockMessage() ?? $status->message(),
                ]);
        }

        $result = $pluginMigrations->runPendingFor($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.extensions.migrations_ran' : 'admin.extensions.migrations_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'type' => $extension->type?->value,
                'slug' => $extension->slug,
                'exit_code' => $result->exitCode(),
                'pending_count_before' => $status->pendingCount(),
                'pending_count_after' => $result->status()?->pendingCount(),
                'migrations_path' => $result->status()?->migrationsPath(),
                'blocks' => $result->status()?->blocks() ?? [],
            ],
            request: $request,
        );

        return $this->redirectWithStateChangeFeedback($result);
    }

    public function sync(
        Request $request,
        ExtensionRegistrySynchronizer $synchronizer,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $result = $synchronizer->sync()->toArray();

        $summary = $result['summary'];

        $auditLogger->log(
            action: 'admin.extensions.synced',
            actor: $request->user(),
            summary: 'Executed a manual extension registry sync.',
            metadata: [
                'core_version' => $result['core_version'],
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'unchanged' => $summary['unchanged'],
                'forced_disabled' => $summary['forced_disabled'],
                'total' => $summary['total'],
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.extensions.index')
            ->with('status', sprintf(
                'Extension sync completed. Created %d, updated %d, unchanged %d, forced disabled %d.',
                $summary['created'],
                $summary['updated'],
                $summary['unchanged'],
                $summary['forced_disabled'],
            ));
    }

    public function enable(
        Request $request,
        ExtensionRecord $extension,
        ExtensionOperationEligibilityService $eligibility,
        ExtensionOperationalStateManager $states,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $evaluation = $eligibility->evaluateEnable($extension);

        if (! $evaluation->allowed()) {
            $auditLogger->log(
                action: 'admin.extensions.enable_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                metadata: [
                    'type' => $extension->type?->value,
                    'slug' => $extension->slug,
                    'blocks' => $evaluation->blocks(),
                    'warnings' => $evaluation->warnings(),
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.extensions.index')
                ->withErrors([
                    'extensions' => $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                ]);
        }

        $result = $states->enableRecord($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.extensions.enabled' : 'admin.extensions.enable_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'type' => $extension->type?->value,
                'slug' => $extension->slug,
                'discovery_status' => $extension->discovery_status?->value,
                'operational_status' => $result->record()?->operational_status?->value,
                'warnings' => $evaluation->warnings(),
            ],
            request: $request,
        );

        return $this->redirectWithStateChangeFeedback($result);
    }

    public function install(
        Request $request,
        ExtensionRecord $extension,
        ExtensionOperationEligibilityService $eligibility,
        ExtensionLifecycleStateManager $states,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $evaluation = $eligibility->evaluateInstall($extension);

        if (! $evaluation->allowed()) {
            $auditLogger->log(
                action: 'admin.extensions.install_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                metadata: [
                    'type' => $extension->type?->value,
                    'slug' => $extension->slug,
                    'blocks' => $evaluation->blocks(),
                    'warnings' => $evaluation->warnings(),
                    'lifecycle_status' => $extension->administrativeLifecycleStatus()->value,
                    'operational_status' => $extension->operational_status?->value,
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.extensions.index')
                ->withErrors([
                    'extensions' => $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                ]);
        }

        $result = $states->installRecord($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.extensions.installed' : 'admin.extensions.install_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'type' => $extension->type?->value,
                'slug' => $extension->slug,
                'discovery_status' => $extension->discovery_status?->value,
                'lifecycle_status' => $result->record()?->administrativeLifecycleStatus()->value,
                'operational_status' => $result->record()?->operational_status?->value,
                'warnings' => $evaluation->warnings(),
            ],
            request: $request,
        );

        return $this->redirectWithStateChangeFeedback($result);
    }

    public function disable(
        Request $request,
        ExtensionRecord $extension,
        ExtensionOperationEligibilityService $eligibility,
        ExtensionOperationalStateManager $states,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $evaluation = $eligibility->evaluateDisable($extension);

        if (! $evaluation->allowed()) {
            $auditLogger->log(
                action: 'admin.extensions.disable_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                metadata: [
                    'type' => $extension->type?->value,
                    'slug' => $extension->slug,
                    'blocks' => $evaluation->blocks(),
                    'warnings' => $evaluation->warnings(),
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.extensions.index')
                ->withErrors([
                    'extensions' => $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                ]);
        }

        $result = $states->disableRecord($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.extensions.disabled' : 'admin.extensions.disable_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'type' => $extension->type?->value,
                'slug' => $extension->slug,
                'discovery_status' => $extension->discovery_status?->value,
                'operational_status' => $result->record()?->operational_status?->value,
                'warnings' => $evaluation->warnings(),
            ],
            request: $request,
        );

        return $this->redirectWithStateChangeFeedback($result);
    }

    public function remove(
        Request $request,
        ExtensionRecord $extension,
        ExtensionOperationEligibilityService $eligibility,
        ExtensionLifecycleStateManager $states,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $evaluation = $eligibility->evaluateRemove($extension);

        if (! $evaluation->allowed()) {
            $auditLogger->log(
                action: 'admin.extensions.remove_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                metadata: [
                    'type' => $extension->type?->value,
                    'slug' => $extension->slug,
                    'blocks' => $evaluation->blocks(),
                    'warnings' => $evaluation->warnings(),
                    'lifecycle_status' => $extension->administrativeLifecycleStatus()->value,
                    'operational_status' => $extension->operational_status?->value,
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.extensions.index')
                ->withErrors([
                    'extensions' => $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                ]);
        }

        $result = $states->removeRecord($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.extensions.removed' : 'admin.extensions.remove_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'type' => $extension->type?->value,
                'slug' => $extension->slug,
                'discovery_status' => $extension->discovery_status?->value,
                'lifecycle_status' => $result->record()?->administrativeLifecycleStatus()->value,
                'operational_status' => $result->record()?->operational_status?->value,
                'warnings' => $evaluation->warnings(),
            ],
            request: $request,
        );

        return $this->redirectWithStateChangeFeedback($result);
    }

    protected function redirectWithStateChangeFeedback($result): RedirectResponse
    {
        $redirect = redirect()->route('admin.extensions.index');

        if ($result->success()) {
            return $redirect->with('status', $result->message());
        }

        return $redirect->withErrors([
            'extensions' => $result->message(),
        ]);
    }
}
