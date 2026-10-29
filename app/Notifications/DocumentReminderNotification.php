<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Document $document, public readonly string $reminderType) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_expiry_reminder',
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'reference_number' => $this->document->reference_number,
            'reminder_type' => $this->reminderType,
            'expiry_date' => $this->document->expiry_date?->toDateString(),
            'message' => sprintf(
                'Document "%s" (%s) expires on %s.',
                $this->document->title,
                $this->document->reference_number,
                $this->document->expiry_date?->toDateString() ?? 'an unknown date',
            ),
        ];
    }
}
