<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'expiry_reminder_days',
        'expiry_reminder_recipients',
        'next_review_reminder_days',
        'next_review_reminder_recipients',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expiry_reminder_days' => 'array',
            'expiry_reminder_recipients' => 'array',
            'next_review_reminder_days' => 'array',
            'next_review_reminder_recipients' => 'array',
        ];
    }

    public function documents() { return $this->hasMany(Document::class); }
    public function reviewPolicies() { return $this->hasMany(DocumentTypeReviewPolicy::class); }
    public function activeReviewPolicy() { return $this->hasOne(DocumentTypeReviewPolicy::class)->where('is_active', true); }

    public function expiryReminderDays(): array
    {
        return $this->expiry_reminder_days ?? [90, 30, 7, 0];
    }

    public function expiryReminderRecipients(): array
    {
        return $this->expiry_reminder_recipients ?? ['owner'];
    }

    public function nextReviewReminderDays(): array
    {
        return $this->next_review_reminder_days ?? [30, 7, 0];
    }

    public function nextReviewReminderRecipients(): array
    {
        return $this->next_review_reminder_recipients ?? ['owner', 'document_managers'];
    }
}
