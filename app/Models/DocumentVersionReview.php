<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersionReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_cycle_id', 'document_version_id', 'reviewer_id', 'sequence_number',
        'status', 'decision', 'comments', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'sequence_number' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function cycle() { return $this->belongsTo(DocumentReviewCycle::class, 'review_cycle_id'); }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewer_id'); }
}
