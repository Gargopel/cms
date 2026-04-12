<?php

namespace App\Core\Health\Checks;

use App\Core\Contracts\Health\SystemHealthCheck;
use App\Core\Extensions\Health\ExtensionHealthService;
use App\Core\Health\HealthCheckResult;

class ExtensionsEcosystemHealthCheck implements SystemHealthCheck
{
    public function __construct(
        protected ExtensionHealthService $extensions,
    ) {
    }

    public function key(): string
    {
        return 'extensions_ecosystem';
    }

    public function label(): string
    {
        return 'Extensions Ecosystem';
    }

    public function description(): string
    {
        return 'Diagnostica a saude operacional do ecossistema de extensoes usando registry, manifesto normalizado e dependencias declaradas.';
    }

    public function run(): HealthCheckResult
    {
        $report = $this->extensions->report();
        $summary = $report->summary();

        $message = match ($report->overallStatus()->value) {
            'error' => 'Existem inconsistencias operacionais relevantes em extensoes habilitadas ou criticas.',
            'warning' => 'Existem alertas operacionais de extensoes que merecem revisao administrativa.',
            default => 'O ecossistema de extensoes nao apresenta problemas operacionais relevantes nesta leitura.',
        };

        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            description: $this->description(),
            status: $report->overallStatus(),
            message: $message,
            meta: [
                'summary' => $summary,
                'top_issues' => $report->topIssues(),
            ],
        );
    }
}
