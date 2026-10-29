<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function documents() { return $this->hasMany(Document::class); }
    public function reviewPolicies() { return $this->hasMany(DocumentTypeReviewPolicy::class); }
    public function activeReviewPolicy() { return $this->hasOne(DocumentTypeReviewPolicy::class)->where('is_active', true); }
}
