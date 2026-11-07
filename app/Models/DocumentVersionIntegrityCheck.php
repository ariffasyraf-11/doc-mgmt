<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersionIntegrityCheck extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_version_id',
        'checked_by',
        'stored_checksum',
        'calculated_checksum',
        'status',
        'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function version()
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
