<?php

namespace App\Core\Extensions\Registry;

use App\Core\Extensions\Models\ExtensionRecord;

class ExtensionRegistrySyncResult
{
    /**
     * @param  array<int, ExtensionRecord>  $records
     */
    public function __construct(
        protected string $coreVersion,
        protected int $created,
        protected int $updated,
        protected int $unchanged,
        protected int $forcedDisabled,
        protected array $records,
    ) {
    }

    public function toArray(): array
    {
        return [
            'core_version' => $this->coreVersion,
            'summary' => [
                'created' => $this->created,
                'updated' => $this->updated,
                'unchanged' => $this->unchanged,
                'forced_disabled' => $this->forcedDisabled,
                'total' => count($this->records),
            ],
            'records' => array_map(
                static fn (ExtensionRecord $record): array => $record->toRegistryArray(),
                $this->records
            ),
        ];
    }
}
