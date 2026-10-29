<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentDownload extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_version_id', 'user_id', 'outcome', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function version()
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
