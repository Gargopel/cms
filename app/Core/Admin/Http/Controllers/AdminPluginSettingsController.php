<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Audit\AdminAuditLogger;
use App\Core\Extensions\Models\ExtensionRecord;
use App\Core\Extensions\Settings\PluginSettingsCatalog;
use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminPluginSettingsController extends Controller
{
    public function edit(ExtensionRecord $extension, PluginSettingsManager $pluginSettings): View
    {
        $catalog = $this->catalogOrFail($pluginSettings, $extension);

        abort_unless(request()->user()?->can($catalog->resolvedPermission()) ?? false, 403);

        return view('admin.extensions.settings.edit', [
            'pageTitle' => $catalog->pluginName().' Settings',
            'pageSubtitle' => 'Configuracoes persistidas do plugin elegivel, resolvidas pelo core sem depender do provider estar ativo.',
            'extension' => $extension,
            'catalog' => $catalog,
            'fields' => $catalog->fields(),
            'values' => $pluginSettings->valuesFor($extension),
            'canManageSettings' => request()->user()?->can($catalog->resolvedPermission()) ?? false,
        ]);
    }

    public function update(
        Request $request,
        ExtensionRecord $extension,
        PluginSettingsManager $pluginSettings,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $catalog = $this->catalogOrFail($pluginSettings, $extension);

        abort_unless($request->user()?->can($catalog->resolvedPermission()) ?? false, 403);

        $normalized = $pluginSettings->normalizeInput($catalog, $request->all());
        $validated = Validator::make($normalized, $catalog->validationRules())->validate();

        $pluginSettings->update($extension, $validated);

        $auditLogger->log(
            action: 'admin.plugin_settings.updated',
            actor: $request->user(),
            target: $extension,
            summary: 'Updated persisted settings for plugin '.$extension->slug.'.',
            metadata: [
                'plugin' => $extension->slug,
                'group' => $catalog->groupName(),
                'changed_keys' => array_keys($validated),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.extensions.settings.edit', $extension)
            ->with('status', 'Plugin settings updated successfully.');
    }

    protected function catalogOrFail(PluginSettingsManager $pluginSettings, ExtensionRecord $extension): PluginSettingsCatalog
    {
        $catalog = $pluginSettings->catalogFor($extension);

        if (! $catalog instanceof PluginSettingsCatalog) {
            abort(404);
        }

        return $catalog;
    }
}
