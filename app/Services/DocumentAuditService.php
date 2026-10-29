<?php

namespace App\Services;

use App\Models\DocumentAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DocumentAuditService
{
    public function record(?int $actorId, string $action, Model $subject, array $metadata = [], ?Request $request = null): DocumentAuditLog
    {
        return DocumentAuditLog::query()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
