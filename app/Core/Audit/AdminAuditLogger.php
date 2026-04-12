<?php

namespace App\Core\Audit;

use App\Core\Audit\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $action,
        ?object $actor = null,
        ?Model $target = null,
        ?string $summary = null,
        array $metadata = [],
        ?Request $request = null,
    ): ?AdminAuditLog {
        if (! $this->isAvailable()) {
            return null;
        }

        $targetType = null;
        $targetId = null;

        if ($target) {
            $targetType = $target->getMorphClass() ?: $target::class;
            $targetId = $target->getKey() ? (string) $target->getKey() : null;
        }

        /** @var AdminAuditLog $log */
        $log = AdminAuditLog::query()->create([
            'action' => $action,
            'user_id' => method_exists($actor, 'getAuthIdentifier') ? $actor->getAuthIdentifier() : null,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'summary' => $summary,
            'metadata' => $this->sanitizeMetadata($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $this->truncateUserAgent($request?->userAgent()),
        ]);

        return $log;
    }

    public function isAvailable(): bool
    {
        try {
            return Schema::hasTable('admin_audit_logs');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function sanitizeMetadata(array $metadata): array
    {
        return collect($metadata)
            ->reject(static fn (mixed $value, string|int $key): bool => in_array((string) $key, [
                'password',
                'password_confirmation',
                'current_password',
                'global_scripts',
            ], true))
            ->map(function (mixed $value): mixed {
                if (is_string($value) && mb_strlen($value) > 500) {
                    return mb_substr($value, 0, 500).'...';
                }

                return $value;
            })
            ->all();
    }

    protected function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 1000);
    }
}
