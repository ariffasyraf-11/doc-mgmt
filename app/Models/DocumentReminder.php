<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id', 'document_version_id', 'recipient_id', 'reminder_type',
        'due_date', 'status', 'attempts', 'sent_at', 'last_error',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'attempts' => 'integer', 'sent_at' => 'datetime'];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
    public function recipient() { return $this->belongsTo(User::class, 'recipient_id'); }
}
