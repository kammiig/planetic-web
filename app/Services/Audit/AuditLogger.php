<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Central audit logger (Security & Access §8.3, §13.6). Records who did what,
 * with old/new values, IP and user agent. Audit logs are append-only and must
 * never be deleted from the normal admin UI.
 */
class AuditLogger
{
    /**
     * @param  array{old?: array<string, mixed>, new?: array<string, mixed>}  $changes
     */
    public function log(
        string $action,
        ?Model $entity = null,
        ?Request $request = null,
        array $changes = [],
        ?string $description = null,
        ?User $actor = null,
    ): AuditLog {
        $request ??= request();
        $actor ??= Auth::user();

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a model update, capturing its dirty attributes as old/new values.
     * Call BEFORE saving, or pass the model after save() — uses getChanges().
     */
    public function logModelChange(string $action, Model $model, ?string $description = null): AuditLog
    {
        $new = $model->getChanges();
        $old = array_intersect_key($model->getOriginal(), $new);

        return $this->log($action, $model, null, ['old' => $old, 'new' => $new], $description);
    }
}
