<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersionReview;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
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
        return FilamentNotification::make()
            ->title('Document review assigned')
            ->body('You have been assigned to review: '.$this->document->title)
            ->actions([
                Action::make('view')
                    ->label('View document')
                    ->url(route('documents.workflow.show', $this->document))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
