<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Admin\Support\AdminExtensionPointService;
use App\Core\Admin\Support\CoreAdminOverviewService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        CoreAdminOverviewService $overview,
        AdminExtensionPointService $extensionPoints,
    ): View
    {
        return view('admin.dashboard.index', [
            'pageTitle' => 'Core Dashboard',
            'pageSubtitle' => 'Operacao central da plataforma, com foco em extensoes, observabilidade e manutencao segura.',
            'metrics' => $overview->dashboardMetrics(),
            'statusSummary' => $overview->extensionStatusSummary(),
            'bootstrapReport' => $overview->bootstrapReport(),
            'recentExtensions' => $overview->recentExtensions(),
            'healthSummary' => $overview->healthSummary(),
            'extensionDashboardPanels' => $extensionPoints->visibleDashboardPanels(auth()->user()),
        ]);
    }
}
