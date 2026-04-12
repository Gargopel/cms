<?php

namespace App\Core\Health;

use App\Core\Health\Enums\HealthStatus;

class SystemHealthReport
{
    /**
     * @param  array<int, HealthCheckResult>  $checks
     */
    public function __construct(
        public readonly array $checks,
    ) {
    }

    public function overallStatus(): HealthStatus
    {
        if (collect($this->checks)->contains(fn (HealthCheckResult $check): bool => $check->status === HealthStatus::Error)) {
            return HealthStatus::Error;
        }

        if (collect($this->checks)->contains(fn (HealthCheckResult $check): bool => $check->status === HealthStatus::Warning)) {
            return HealthStatus::Warning;
        }

        return HealthStatus::Ok;
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'total' => count($this->checks),
            'ok' => collect($this->checks)->where('status', HealthStatus::Ok)->count(),
            'warning' => collect($this->checks)->where('status', HealthStatus::Warning)->count(),
            'error' => collect($this->checks)->where('status', HealthStatus::Error)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'overall_status' => $this->overallStatus()->value,
            'summary' => $this->summary(),
            'checks' => array_map(
                static fn (HealthCheckResult $check): array => $check->toArray(),
                $this->checks,
            ),
        ];
    }
}
