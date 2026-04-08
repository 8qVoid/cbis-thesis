<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait LogsAudit
{
    protected function logAudit(string $action, ?Model $model = null, array $details = [], ?Request $request = null): void
    {
        $user = auth()->user();

        AuditLog::create([
            'facility_id' => $user?->facility_id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'details' => $details,
            'ip_address' => $request?->ip(),
        ]);
    }
}
