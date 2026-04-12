<?php

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Enums\ExtensionLifecycleStatus;
use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Permissions\PluginPermissionRegistrySynchronizer;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Operations\ExtensionOperationEligibilityService;

class ExtensionLifecycleStateManager
{
    public function __construct(
        protected ExtensionOperationEligibilityService $eligibility,
        protected PluginPermissionRegistrySynchronizer $pluginPermissions,
    ) {
    }

    public function install(ExtensionType $type, string $slug): ExtensionStateChangeResult
    {
        $record = $this->findByTypeAndSlug($type, $slug);

        if (! $record instanceof ExtensionRecord) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: 'Extension record was not found.',
            );
        }

        return $this->installRecord($record);
    }

    public function installRecord(ExtensionRecord $record): ExtensionStateChangeResult
    {
        $evaluation = $this->eligibility->evaluateInstall($record);

        if (! $evaluation->allowed()) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                record: $record,
            );
        }

        $record->lifecycle_status = ExtensionLifecycleStatus::Installed;

        if ($record->operational_status !== ExtensionOperationalStatus::Enabled) {
            $record->operational_status = ExtensionOperationalStatus::Disabled;
        }

        $record->save();
        $this->pluginPermissions->sync();

        return new ExtensionStateChangeResult(
            success: true,
            changed: true,
            message: 'Extension was installed into the administrative registry successfully.',
            record: $record->fresh(),
        );
    }

    public function remove(ExtensionType $type, string $slug): ExtensionStateChangeResult
    {
        $record = $this->findByTypeAndSlug($type, $slug);

        if (! $record instanceof ExtensionRecord) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: 'Extension record was not found.',
            );
        }

        return $this->removeRecord($record);
    }

    public function removeRecord(ExtensionRecord $record): ExtensionStateChangeResult
    {
        $evaluation = $this->eligibility->evaluateRemove($record);

        if (! $evaluation->allowed()) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                record: $record,
            );
        }

        $record->lifecycle_status = ExtensionLifecycleStatus::Removed;
        $record->operational_status = ExtensionOperationalStatus::Disabled;
        $record->save();
        $this->pluginPermissions->sync();

        return new ExtensionStateChangeResult(
            success: true,
            changed: true,
            message: 'Extension was removed from the administrative registry successfully.',
            record: $record->fresh(),
        );
    }

    protected function findByTypeAndSlug(ExtensionType $type, string $slug): ?ExtensionRecord
    {
        return ExtensionRecord::query()
            ->where('type', $type->value)
            ->where('slug', $slug)
            ->first();
    }
}
