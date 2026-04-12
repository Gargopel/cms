<?php

namespace App\Core\Admin\Support;

use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use App\Core\Extensions\Hooks\ExtensionHookRegistry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class AdminExtensionPointService
{
    public function __construct(
        protected ExtensionHookRegistry $registry,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function visibleNavigationItems(?Authenticatable $user = null, ?string $currentRouteName = null): array
    {
        return collect($this->registry->adminNavigationItems())
            ->filter(fn (AdminNavigationItem $item): bool => $this->isVisible($item->requiredPermission(), $user))
            ->map(function (AdminNavigationItem $item) use ($currentRouteName): array {
                return array_merge($item->toArray(), [
                    'active' => $currentRouteName !== null
                        && $item->activeWhen() !== null
                        && Str::is($item->activeWhen(), $currentRouteName),
                ]);
            })
            ->sortBy(static fn (array $item): string => $item['plugin_slug'].'|'.$item['label'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function visibleDashboardPanels(?Authenticatable $user = null): array
    {
        return collect($this->registry->adminDashboardPanels())
            ->filter(fn (AdminDashboardPanel $panel): bool => $this->isVisible($panel->requiredPermission(), $user))
            ->map(static fn (AdminDashboardPanel $panel): array => $panel->toArray())
            ->sortBy(static fn (array $panel): string => $panel['plugin_slug'].'|'.$panel['title'])
            ->values()
            ->all();
    }

    protected function isVisible(?string $requiredPermission, ?Authenticatable $user): bool
    {
        if ($requiredPermission === null) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return Gate::forUser($user)->check($requiredPermission);
    }
}
