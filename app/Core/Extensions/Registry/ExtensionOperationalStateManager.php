<?php

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Enums\ExtensionOperationalStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Operations\ExtensionOperationEligibilityService;

class ExtensionOperationalStateManager
{
    public function __construct(
        protected ExtensionOperationEligibilityService $eligibility,
    ) {
    }

    public function enable(ExtensionType $type, string $slug): ExtensionStateChangeResult
    {
        $record = $this->findByTypeAndSlug($type, $slug);

        if (! $record instanceof ExtensionRecord) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: 'Extension record was not found.',
            );
        }

        return $this->enableRecord($record);
    }

    public function enableRecord(ExtensionRecord $record): ExtensionStateChangeResult
    {
        $evaluation = $this->eligibility->evaluateEnable($record);

        if (! $evaluation->allowed()) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                record: $record,
            );
        }

        $record->operational_status = ExtensionOperationalStatus::Enabled;
        $record->save();

        return new ExtensionStateChangeResult(
            success: true,
            changed: true,
            message: 'Extension was enabled successfully.',
            record: $record->fresh(),
        );
    }

    public function disable(ExtensionType $type, string $slug): ExtensionStateChangeResult
    {
        $record = $this->findByTypeAndSlug($type, $slug);

        if (! $record instanceof ExtensionRecord) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: 'Extension record was not found.',
            );
        }

        return $this->disableRecord($record);
    }

    public function disableRecord(ExtensionRecord $record): ExtensionStateChangeResult
    {
        $evaluation = $this->eligibility->evaluateDisable($record);

        if (! $evaluation->allowed()) {
            return new ExtensionStateChangeResult(
                success: false,
                changed: false,
                message: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                record: $record,
            );
        }

        $record->operational_status = ExtensionOperationalStatus::Disabled;
        $record->save();

        return new ExtensionStateChangeResult(
            success: true,
            changed: true,
            message: 'Extension was disabled successfully.',
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
