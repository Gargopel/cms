<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Health\SystemHealthService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminSystemHealthController extends Controller
{
    public function __invoke(SystemHealthService $health): View
    {
        $report = $health->run();

        return view('admin.health.index', [
            'pageTitle' => 'System Health',
            'pageSubtitle' => 'Diagnostico operacional minimo do core para instalacao, banco, cache, escrita e saude do ecossistema de extensoes.',
            'report' => $report->toArray(),
        ]);
    }
}
