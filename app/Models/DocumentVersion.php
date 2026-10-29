<?php

namespace App\Models;

use App\Enums\DocumentVersionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id', 'version_number', 'file_path', 'original_filename', 'mime_type',
        'file_size', 'checksum', 'change_notes', 'uploaded_by', 'status', 'submitted_at',
        'approved_at', 'published_at', 'supersedes_version_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentVersionStatus::class,
            'version_number' => 'integer',
            'file_size' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function supersedes() { return $this->belongsTo(self::class, 'supersedes_version_id'); }
    public function reviewCycles() { return $this->hasMany(DocumentReviewCycle::class); }
    public function reviews() { return $this->hasMany(DocumentVersionReview::class); }
}
