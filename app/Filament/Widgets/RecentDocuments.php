<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDocuments extends TableWidget
{
    protected static ?string $heading = 'Recently updated documents';

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return DocumentResource::getEloquentQuery()
            ->with(['department', 'documentType'])
            ->latest('updated_at')
            ->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('reference_number')->label('Reference')->searchable(),
            TextColumn::make('title')->searchable()->limit(55),
            TextColumn::make('department.name')->label('Department'),
            TextColumn::make('documentType.name')->label('Type'),
            TextColumn::make('workflow_status')->badge(),
            TextColumn::make('expiry_date')->date()->placeholder('No expiry'),
            TextColumn::make('updated_at')->dateTime()->since()->label('Last updated'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->paginated(false)
            ->emptyStateHeading('No documents available')
            ->emptyStateDescription('Documents you have permission to access will appear here.');
    }
}
