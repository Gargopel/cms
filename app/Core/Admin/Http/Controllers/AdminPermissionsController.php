<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Auth\Models\Permission;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminPermissionsController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('scope')
            ->orderBy('name')
            ->paginate(50);

        $permissions->getCollection()->transform(function (Permission $permission): Permission {
            $permission->setAttribute('scope_label', $this->scopeLabel($permission->scope));
            $permission->setAttribute('origin_label', $this->originLabel($permission->scope));

            return $permission;
        });

        return view('admin.permissions.index', [
            'pageTitle' => 'Permissions',
            'pageSubtitle' => 'Catalogo central de permissoes do core e de plugins instalados, com escopo explicito e governanca unificada.',
            'permissions' => $permissions,
        ]);
    }

    protected function scopeLabel(string $scope): string
    {
        if ($scope === 'core') {
            return 'Core';
        }

        if (str_starts_with($scope, 'plugin:')) {
            return 'Plugin';
        }

        return ucfirst($scope);
    }

    protected function originLabel(string $scope): string
    {
        if ($scope === 'core') {
            return 'Core platform';
        }

        if (str_starts_with($scope, 'plugin:')) {
            return 'Plugin '.substr($scope, strlen('plugin:'));
        }

        return $scope;
    }
}
