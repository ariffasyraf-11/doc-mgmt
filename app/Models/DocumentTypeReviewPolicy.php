<?php

namespace App\Models;

use App\Enums\ReviewMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTypeReviewPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['document_type_id', 'review_mode', 'required_reviewers', 'owner_can_review', 'rejection_mode', 'is_active'];

    protected function casts(): array
    {
        return [
            'review_mode' => ReviewMode::class,
            'required_reviewers' => 'integer',
            'owner_can_review' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function documentType() { return $this->belongsTo(DocumentType::class); }
}
