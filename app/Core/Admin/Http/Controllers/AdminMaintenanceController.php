<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Support\CoreAdminOverviewService;
use App\Core\Audit\AdminAuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class AdminMaintenanceController extends Controller
{
    public function __invoke(CoreAdminOverviewService $overview): View
    {
        return view('admin.maintenance.index', [
            'pageTitle' => 'Maintenance',
            'pageSubtitle' => 'Acoes operacionais seguras e leitura basica do ambiente do core nesta fase do produto.',
            'systemStatus' => $overview->systemStatus(),
            'bootstrapReport' => $overview->bootstrapReport(),
        ]);
    }

    public function clearApplicationCache(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        Artisan::call('cache:clear');

        $auditLogger->log(
            action: 'admin.maintenance.application_cache_cleared',
            actor: $request->user(),
            summary: 'Cleared application cache from admin maintenance.',
            metadata: [
                'command' => 'cache:clear',
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.maintenance')
            ->with('status', 'Application cache cleared successfully.');
    }

    public function clearCompiledViews(Request $request, AdminAuditLogger $auditLogger): RedirectResponse
    {
        Artisan::call('view:clear');

        $auditLogger->log(
            action: 'admin.maintenance.views_cleared',
            actor: $request->user(),
            summary: 'Cleared compiled views from admin maintenance.',
            metadata: [
                'command' => 'view:clear',
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.maintenance')
            ->with('status', 'Compiled views cleared successfully.');
    }
}
