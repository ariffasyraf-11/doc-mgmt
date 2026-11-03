<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly string $reminderType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isReviewReminder = str_starts_with($this->reminderType, 'next_review_');
        $date = $isReviewReminder ? $this->document->next_review_date : $this->document->expiry_date;
        $dateLabel = $isReviewReminder ? 'next review date' : 'expiry date';
        $message = $isReviewReminder
            ? sprintf('Document "%s" (%s) is due for review on %s.', $this->document->title, $this->document->reference_number, $date?->toDateString() ?? 'an unknown date')
            : sprintf('Document "%s" (%s) expires on %s.', $this->document->title, $this->document->reference_number, $date?->toDateString() ?? 'an unknown date');

        return [
            'type' => $isReviewReminder ? 'document_next_review_reminder' : 'document_expiry_reminder',
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'reference_number' => $this->document->reference_number,
            'reminder_type' => $this->reminderType,
            'reminder_date' => $date?->toDateString(),
            'date_label' => $dateLabel,
            'message' => $message,
            'url' => route('documents.workflow.show', ['document' => $this->document->id]),
        ];
    }
}
