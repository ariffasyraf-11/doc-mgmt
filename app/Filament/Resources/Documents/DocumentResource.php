<?php

namespace App\Filament\Resources\Documents;

use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Documents';
    protected static ?string $modelLabel = 'document';
    protected static ?string $pluralModelLabel = 'documents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('reference_number')->label('Reference number')->maxLength(100)->unique(ignoreRecord: true),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Select::make('category_id')->relationship('category', 'name')->searchable()->preload()->required(),
            Select::make('document_type_id')->relationship('documentType', 'name')->searchable()->preload()->required(),
            Select::make('department_id')->relationship('department', 'name')->searchable()->preload()->required(),
            Select::make('visibility')->options(DocumentVisibility::options())->required()->default(DocumentVisibility::Department->value),
            DatePicker::make('effective_date'),
            DatePicker::make('expiry_date')->afterOrEqual('effective_date'),
            DatePicker::make('next_review_date'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable()->limit(50),
                TextColumn::make('department.name')->label('Department')->sortable(),
                TextColumn::make('documentType.name')->label('Type')->sortable(),
                TextColumn::make('workflow_status')->badge(),
                TextColumn::make('expiry_date')->date()->sortable()->color(fn (Document $record) => $record->isExpired() ? 'danger' : null),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visibility')->options(DocumentVisibility::options()),
                SelectFilter::make('workflow_status')->options(collect(DocumentWorkflowStatus::cases())->mapWithKeys(fn ($status) => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])->all()),
            ])
            ->recordActions([
                Action::make('manageVersions')
                    ->label('Versions / workflow')
                    ->url(fn (Document $record): string => route('documents.workflow.show', $record))
                    ->visible(fn (Document $record): bool => Auth::user()?->can('view', $record) ?? false),
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) return $query->whereRaw('1 = 0');

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('owner_id', $user->id)
                ->orWhere('visibility', DocumentVisibility::Organization->value)
                ->orWhere(function (Builder $department) use ($user) {
                    $department->where('visibility', DocumentVisibility::Department->value)->where('department_id', $user->department_id);
                })
                ->orWhereHas('accessGrants', fn (Builder $grants) => $grants->where('user_id', $user->id))
                ->orWhereHas('versions.reviews', fn (Builder $reviews) => $reviews
                    ->where('reviewer_id', $user->id)
                    ->whereHas('cycle', fn (Builder $cycles) => $cycles->where('status', 'in_progress')));

            if ($user->isAdministrator() || $user->isDocumentManager()) {
                $visible->orWhereIn('visibility', [DocumentVisibility::Organization->value, DocumentVisibility::Department->value]);
            }
        });
    }

    public static function canViewAny(): bool { return Auth::check() && Auth::user()->can('viewAny', Document::class); }
    public static function canCreate(): bool { return Auth::check() && Auth::user()->can('create', Document::class); }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
