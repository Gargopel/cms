<?php

namespace App\Core\Auth;

use App\Core\Auth\Enums\CoreRole;
use App\Core\Auth\Support\PermissionSynchronizer;
use App\Core\Auth\Support\SecurityGovernanceService;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionSynchronizer::class);
        $this->app->singleton(SecurityGovernanceService::class);
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole(CoreRole::Administrator->value)) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}
