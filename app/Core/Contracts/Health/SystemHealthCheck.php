<?php

namespace App\Core\Contracts\Health;

use App\Core\Health\HealthCheckResult;

interface SystemHealthCheck
{
    public function key(): string;

    public function label(): string;

    public function description(): string;

    public function run(): HealthCheckResult;
}
