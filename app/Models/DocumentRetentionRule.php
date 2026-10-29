<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRetentionRule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'document_type_id', 'retention_period_days', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'retention_period_days' => 'integer'];
    }

    public function documentType() { return $this->belongsTo(DocumentType::class); }
}
