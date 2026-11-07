<?php

namespace App\Filament\Widgets;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\DocumentVersionReview;
use App\Services\DocumentWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class PendingReviewQueue extends TableWidget
{
    protected static ?string $heading = 'My pending reviews';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->isReviewer() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DocumentVersionReview::query()
                    ->where('reviewer_id', Auth::id())
                    ->where('status', ReviewStatus::Pending->value)
                    ->with(['version.document', 'cycle'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('version.document.reference_number')->label('Reference')->searchable(),
                TextColumn::make('version.document.title')->label('Document')->searchable()->wrap(),
                TextColumn::make('version.version_number')->label('Version'),
                TextColumn::make('cycle.started_at')->label('Assigned')->dateTime(),
                TextColumn::make('sequence_number')->label('Review order'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open document')
                    ->url(fn (DocumentVersionReview $record): string => DocumentResource::getUrl('workflow', ['record' => $record->version->document]))
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (DocumentVersionReview $record) => app(DocumentWorkflowService::class)
                        ->decide($record, Auth::user(), ReviewStatus::Approved)),
                Action::make('reject')
                    ->color('danger')
                    ->form([Textarea::make('comments')->required()->minLength(5)->rows(3)])
                    ->action(fn (DocumentVersionReview $record, array $data) => app(DocumentWorkflowService::class)
                        ->decide($record, Auth::user(), ReviewStatus::Rejected, $data['comments'])),
            ])
            ->emptyStateHeading('No pending reviews')
            ->emptyStateDescription('New review assignments will appear here.');
    }
}
