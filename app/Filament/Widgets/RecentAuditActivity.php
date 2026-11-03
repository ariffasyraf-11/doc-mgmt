<?php

namespace App\Filament\Widgets;

use App\Models\DocumentAuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class RecentAuditActivity extends TableWidget
{
    protected static ?string $heading = 'Recent audit activity';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool) ($user?->isAdministrator() || $user?->isDocumentManager());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DocumentAuditLog::query()->with('actor')->latest('created_at'))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('actor.name')->label('Performed by')->placeholder('System')->searchable(),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('subject_type')->label('Record type')->formatStateUsing(fn (?string $state) => class_basename($state ?? 'Unknown')),
                TextColumn::make('subject_id')->label('Record ID'),
                TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No audit activity recorded');
    }
}
