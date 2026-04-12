<?php

namespace App\Core\Admin\Http\Controllers;

use App\Core\Audit\Models\AdminAuditLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class AdminAuditLogsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'action' => trim((string) $request->query('action', '')),
            'user_id' => trim((string) $request->query('user_id', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
        ];

        $logs = $this->queryLogs($filters);

        return view('admin.audit.index', [
            'pageTitle' => 'Audit Logs',
            'pageSubtitle' => 'Leitura operacional minima das acoes sensiveis registradas pelo core para login, governanca, settings e manutencao.',
            'filters' => $filters,
            'logs' => $logs,
            'actionOptions' => $this->actionOptions(),
            'users' => User::query()->orderBy('name')->limit(100)->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function queryLogs(array $filters): LengthAwarePaginator
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return new LengthAwarePaginator([], 0, 20);
        }

        return AdminAuditLog::query()
            ->with('user')
            ->when($filters['action'] !== '', function ($query) use ($filters): void {
                $query->where('action', $filters['action']);
            })
            ->when($filters['user_id'] !== '', function ($query) use ($filters): void {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->when($filters['date_from'] !== '', function ($query) use ($filters): void {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'] !== '', function ($query) use ($filters): void {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array<int, string>
     */
    protected function actionOptions(): array
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return [];
        }

        return AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }
}
