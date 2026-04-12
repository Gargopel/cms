<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Audit\AdminAuditLogger;
use App\Core\Auth\Enums\CorePermission;
use App\Core\Extensions\Enums\ExtensionType;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Themes\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminThemesController extends Controller
{
    public function index(ThemeManager $themes): View
    {
        $records = ExtensionRecord::query()
            ->where('type', ExtensionType::Theme->value)
            ->orderBy('name')
            ->orderBy('slug')
            ->paginate(20);

        $activeTheme = $themes->activeTheme();
        $themeStates = [];

        foreach ($records as $theme) {
            $eligibility = $themes->eligibilityFor($theme)->toArray();

            $themeStates[$theme->getKey()] = [
                'is_active' => $themes->isActive($theme),
                'eligibility' => $eligibility,
                'views_path' => $themes->viewsPathFor($theme),
                'assets_path' => $themes->assetsPathFor($theme),
                'has_views' => is_dir($themes->viewsPathFor($theme)),
                'has_assets' => is_dir($themes->assetsPathFor($theme)),
            ];
        }

        return view('admin.themes.index', [
            'pageTitle' => 'Themes',
            'pageSubtitle' => 'Selecione o tema ativo da instancia com fallback seguro para views padrao do core.',
            'themes' => $records,
            'themeStates' => $themeStates,
            'activeTheme' => $activeTheme,
            'canManageThemes' => request()->user()?->can(CorePermission::ManageThemes->value) ?? false,
            'summary' => [
                'total' => ExtensionRecord::query()->where('type', ExtensionType::Theme->value)->count(),
                'valid' => ExtensionRecord::query()->where('type', ExtensionType::Theme->value)->where('discovery_status', 'valid')->count(),
                'installed' => ExtensionRecord::query()->where('type', ExtensionType::Theme->value)->where('lifecycle_status', 'installed')->count(),
            ],
        ]);
    }

    public function activate(
        Request $request,
        ExtensionRecord $extension,
        ThemeManager $themes,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $evaluation = $themes->eligibilityFor($extension);

        if (! $evaluation->allowed()) {
            $auditLogger->log(
                action: 'admin.themes.activation_blocked',
                actor: $request->user(),
                target: $extension,
                summary: $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                metadata: [
                    'slug' => $extension->slug,
                    'type' => $extension->type?->value,
                    'blocks' => $evaluation->blocks(),
                    'warnings' => $evaluation->warnings(),
                ],
                request: $request,
            );

            return redirect()
                ->route('admin.themes.index')
                ->withErrors([
                    'themes' => $evaluation->primaryBlockMessage() ?? $evaluation->message(),
                ]);
        }

        $result = $themes->activateRecord($extension);

        $auditLogger->log(
            action: $result->success() ? 'admin.themes.activated' : 'admin.themes.activation_blocked',
            actor: $request->user(),
            target: $extension,
            summary: $result->message(),
            metadata: [
                'success' => $result->success(),
                'changed' => $result->changed(),
                'slug' => $extension->slug,
                'discovery_status' => $extension->discovery_status?->value,
                'lifecycle_status' => $result->theme()?->administrativeLifecycleStatus()->value,
                'warnings' => $evaluation->warnings(),
            ],
            request: $request,
        );

        $redirect = redirect()->route('admin.themes.index');

        if ($result->success()) {
            return $redirect->with('status', $result->message());
        }

        return $redirect->withErrors([
            'themes' => $result->message(),
        ]);
    }
}
