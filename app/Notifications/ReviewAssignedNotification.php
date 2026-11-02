<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersionReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReviewAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly DocumentVersionReview $review,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Document review assigned',
            'body' => 'You have been assigned to review: '.$this->document->title,
            'document_id' => $this->document->id,
            'document_reference' => $this->document->reference_number,
            'review_id' => $this->review->id,
            'url' => route('documents.workflow.show', $this->document),
        ];
    }
}
