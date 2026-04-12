<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Http\Requests\UpdateCoreSettingsRequest;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Auth\Enums\CorePermission;
use App\Core\Settings\CoreSettingsCatalog;
use App\Core\Settings\CoreSettingsManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(CoreSettingsCatalog $catalog, CoreSettingsManager $settings): View
    {
        return view('admin.settings.edit', [
            'pageTitle' => 'Global Settings',
            'pageSubtitle' => 'Configuracoes globais minimas do core para nome da instancia, locale, timezone e contatos operacionais.',
            'groupName' => 'general',
            'fields' => $catalog->group('general'),
            'values' => $settings->group('general'),
            'canManageSettings' => request()->user()?->can(CorePermission::ManageSettings->value) ?? false,
        ]);
    }

    public function update(UpdateCoreSettingsRequest $request, CoreSettingsManager $settings, AdminAuditLogger $auditLogger): RedirectResponse
    {
        $payload = $request->validated();

        $settings->updateGroup('general', $payload);
        $settings->applyRuntimeConfiguration();

        $auditLogger->log(
            action: 'admin.settings.updated',
            actor: $request->user(),
            summary: 'Updated global core settings.',
            metadata: [
                'group' => 'general',
                'site_name' => $payload['site_name'] ?? null,
                'system_email' => $payload['system_email'] ?? null,
                'timezone' => $payload['timezone'] ?? null,
                'locale' => $payload['locale'] ?? null,
                'changed_keys' => array_keys($payload),
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Global settings updated successfully.');
    }
}
