<?php

namespace App\Filament\Resources\ReviewPolicies;

use App\Enums\ReviewMode;
use App\Filament\Resources\ReviewPolicies\Pages\CreateReviewPolicy;
use App\Filament\Resources\ReviewPolicies\Pages\EditReviewPolicy;
use App\Filament\Resources\ReviewPolicies\Pages\ListReviewPolicies;
use App\Models\DocumentTypeReviewPolicy;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentTypeReviewPolicyResource extends Resource
{
    protected static ?string $model = DocumentTypeReviewPolicy::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Review Policies';
    protected static ?string $modelLabel = 'review policy';
    protected static ?string $pluralModelLabel = 'review policies';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('document_type_id')
                ->relationship('documentType', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->unique(ignoreRecord: true),
            Select::make('review_mode')->options(ReviewMode::options())->required()->native(false),
            TextInput::make('required_reviewers')->numeric()->integer()->minValue(1)->maxValue(20)->required()->default(1),
            Toggle::make('owner_can_review')->label('Allow owner to review own submission')->default(false),
            Select::make('rejection_mode')
                ->options(['reject_cycle' => 'Reject review cycle and require revision'])
                ->required()
                ->default('reject_cycle'),
            Toggle::make('is_active')->label('Active policy')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('documentType.name')->label('Document type')->sortable(),
            TextColumn::make('review_mode')->badge()->formatStateUsing(fn ($state) => $state instanceof ReviewMode ? $state->label() : str((string) $state)->title()->toString()),
            TextColumn::make('required_reviewers')->label('Minimum reviewers')->sortable(),
            IconColumn::make('owner_can_review')->label('Owner may review')->boolean(),
            TextColumn::make('rejection_mode')->label('Rejection behavior'),
            IconColumn::make('is_active')->label('Active')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()?->isAdministrator()
            ? parent::getEloquentQuery()
            : parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool { return Auth::user()?->isAdministrator() ?? false; }
    public static function canCreate(): bool { return Auth::user()?->isAdministrator() ?? false; }
    public static function canEdit(Model $record): bool { return Auth::user()?->isAdministrator() ?? false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => ListReviewPolicies::route('/'),
            'create' => CreateReviewPolicy::route('/create'),
            'edit' => EditReviewPolicy::route('/{record}/edit'),
        ];
    }
}
