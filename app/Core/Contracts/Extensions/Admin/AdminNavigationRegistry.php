<?php

namespace App\Core\Contracts\Extensions\Admin;

use App\Core\Extensions\Hooks\AdminNavigationItem;

interface AdminNavigationRegistry
{
    public function registerAdminNavigationItem(AdminNavigationItem $item): void;
}
