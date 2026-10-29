<?php

namespace App\Models;

use App\Enums\ReviewCycleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReviewCycle extends Model
{
    use HasFactory;

    protected $fillable = ['document_id', 'document_version_id', 'policy_snapshot', 'status', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'policy_snapshot' => 'array',
            'status' => ReviewCycleStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
    public function reviews() { return $this->hasMany(DocumentVersionReview::class, 'review_cycle_id')->orderBy('sequence_number'); }
}
