<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentDocuments extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recently updated documents')
            ->query(
                DocumentResource::getEloquentQuery()
                    ->with(['department', 'documentType'])
                    ->latest('updated_at')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->searchable(),
                TextColumn::make('title')->searchable()->limit(55),
                TextColumn::make('department.name')->label('Department'),
                TextColumn::make('documentType.name')->label('Type'),
                TextColumn::make('workflow_status')->badge(),
                TextColumn::make('expiry_date')->date()->placeholder('No expiry'),
                TextColumn::make('updated_at')->dateTime()->since()->label('Last updated'),
            ])
            ->paginated(false)
            ->emptyStateHeading('No documents available')
            ->emptyStateDescription('Documents you have permission to access will appear here.');
    }
}
