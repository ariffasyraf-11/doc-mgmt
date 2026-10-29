<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentAccess extends Model
{
    use HasFactory;

    protected $table = 'document_access';

    protected $fillable = ['document_id', 'user_id', 'access_level', 'granted_by'];

    public function document() { return $this->belongsTo(Document::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function grantedBy() { return $this->belongsTo(User::class, 'granted_by'); }
}
