<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'title', 'description', 'category_id', 'document_type_id',
        'department_id', 'owner_id', 'visibility', 'workflow_status', 'effective_date',
        'expiry_date', 'next_review_date', 'current_published_version_id',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'workflow_status' => DocumentWorkflowStatus::class,
            'effective_date' => 'date',
            'expiry_date' => 'date',
            'next_review_date' => 'date',
        ];
    }

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }
    public function versions() { return $this->hasMany(DocumentVersion::class); }
    public function currentPublishedVersion() { return $this->belongsTo(DocumentVersion::class, 'current_published_version_id'); }
    public function accessGrants() { return $this->hasMany(DocumentAccess::class); }
    public function reviewCycles() { return $this->hasMany(DocumentReviewCycle::class); }
    public function auditLogs() { return $this->morphMany(DocumentAuditLog::class, 'subject'); }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->lte(today());
    }
}
