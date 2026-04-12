<?php

namespace App\Core\Extensions\Permissions;

use App\Core\Auth\Models\Permission;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Extensions\Enums\ExtensionDiscoveryStatus;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PluginPermissionRegistrySynchronizer
{
    public function __construct(
        protected PermissionSynchronizer $permissions,
    ) {
    }

    public function sync(): PluginPermissionSyncResult
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('extension_records')) {
            return new PluginPermissionSyncResult(0, 0, 0);
        }

        $eligiblePlugins = ExtensionRecord::query()
            ->where('type', ExtensionType::Plugin->value)
            ->where('discovery_status', ExtensionDiscoveryStatus::Valid->value)
            ->get()
            ->filter(static fn (ExtensionRecord $record): bool => $record->isAdministrativelyInstalled())
            ->values();

        $definitions = [];
        $slugsByScope = [];

        foreach ($eligiblePlugins as $plugin) {
            foreach ($plugin->declaredPluginPermissions() as $permission) {
                $definitions[] = $permission->toPermissionDefinition();
                $slugsByScope[$permission->scope()][] = $permission->slug();
            }
        }

        $synced = $this->permissions->syncPermissions($definitions, 'plugin');
        $removed = [];

        DB::transaction(function () use ($slugsByScope, &$removed): void {
            $pluginPermissions = Permission::query()
                ->where('scope', 'like', 'plugin:%')
                ->get();

            foreach ($pluginPermissions as $permission) {
                $allowedSlugs = $slugsByScope[$permission->scope] ?? null;

                if ($allowedSlugs !== null && in_array($permission->slug, $allowedSlugs, true)) {
                    continue;
                }

                $removed[] = [
                    'scope' => $permission->scope,
                    'slug' => $permission->slug,
                    'name' => $permission->name,
                ];

                $permission->roles()->detach();
                $permission->delete();
            }
        });

        return new PluginPermissionSyncResult(
            eligiblePlugins: $eligiblePlugins->count(),
            syncedPermissions: count($definitions),
            removedPermissions: count($removed),
            synced: $synced->map(static fn (Permission $permission): array => [
                'scope' => $permission->scope,
                'slug' => $permission->slug,
                'name' => $permission->name,
            ])->all(),
            removed: $removed,
        );
    }
}
