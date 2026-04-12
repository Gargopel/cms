<?php

namespace App\Core\Extensions\Permissions;

class PluginPermissionSyncResult
{
    /**
     * @param  array<int, array<string, mixed>>  $synced
     * @param  array<int, array<string, mixed>>  $removed
     */
    public function __construct(
        protected int $eligiblePlugins,
        protected int $syncedPermissions,
        protected int $removedPermissions,
        protected array $synced = [],
        protected array $removed = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'summary' => [
                'eligible_plugins' => $this->eligiblePlugins,
                'synced_permissions' => $this->syncedPermissions,
                'removed_permissions' => $this->removedPermissions,
            ],
            'synced' => $this->synced,
            'removed' => $this->removed,
        ];
    }
}
