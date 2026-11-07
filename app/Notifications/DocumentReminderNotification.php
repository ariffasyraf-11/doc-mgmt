<?php

namespace App\Notifications;

use App\Models\Document;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
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

    public function toDatabase(object $notifiable): array
    {
        $isReviewReminder = str_starts_with($this->reminderType, 'next_review_');
        $date = $isReviewReminder ? $this->document->next_review_date : $this->document->expiry_date;
        $message = $isReviewReminder
            ? sprintf('Document "%s" (%s) is due for review on %s.', $this->document->title, $this->document->reference_number, $date?->toDateString() ?? 'an unknown date')
            : sprintf('Document "%s" (%s) expires on %s.', $this->document->title, $this->document->reference_number, $date?->toDateString() ?? 'an unknown date');

        return FilamentNotification::make()
            ->title($isReviewReminder ? 'Document review due' : 'Document expiry reminder')
            ->body($message)
            ->actions([
                Action::make('view')
                    ->label('View document')
                    ->url(DocumentResource::getUrl('workflow', ['record' => $this->document]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
