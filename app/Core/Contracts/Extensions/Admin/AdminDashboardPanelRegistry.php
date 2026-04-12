<?php

namespace App\Core\Contracts\Extensions\Admin;

use App\Core\Extensions\Hooks\AdminDashboardPanel;

interface AdminDashboardPanelRegistry
{
    public function registerAdminDashboardPanel(AdminDashboardPanel $panel): void;
}
