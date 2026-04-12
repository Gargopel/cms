<?php

namespace App\Core\Health;

use App\Core\Contracts\Health\SystemHealthCheck;

class SystemHealthService
{
    /**
     * @param  array<int, SystemHealthCheck>  $checks
     */
    public function __construct(
        protected array $checks,
    ) {
    }

    public function run(): SystemHealthReport
    {
        return new SystemHealthReport(
            checks: array_map(
                static fn (SystemHealthCheck $check): HealthCheckResult => $check->run(),
                $this->checks,
            ),
        );
    }
}
