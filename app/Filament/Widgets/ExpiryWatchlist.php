<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiryWatchlist extends TableWidget
{
    protected static ?string $heading = 'Expiry watchlist — expired and next 30 days';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DocumentResource::getEloquentQuery()
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<=', today()->addDays(30))
                    ->with(['category', 'documentType', 'department'])
                    ->orderBy('expiry_date')
            )
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->searchable()->copyable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('documentType.name')->label('Type'),
                TextColumn::make('department.name')->label('Department'),
                TextColumn::make('expiry_date')->date()->sortable()
                    ->color(fn (Document $record) => $record->isExpired() ? 'danger' : 'warning'),
                TextColumn::make('workflow_status')->badge(),
            ])
            ->defaultSort('expiry_date')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No expired or upcoming-expiry documents')
            ->emptyStateDescription('Accessible documents expiring within the next 30 days will appear here.');
    }
}
