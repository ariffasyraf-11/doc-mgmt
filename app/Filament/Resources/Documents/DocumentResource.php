<?php

namespace App\Filament\Resources\Documents;

use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ManageDocumentVersionHistory;
use App\Filament\Resources\Documents\Pages\ManageDocumentVersions;
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
        $metadataLocked = fn (?Document $record): bool => $record !== null
            && $record->workflow_status !== DocumentWorkflowStatus::Draft;

        return $schema->components([
            TextInput::make('reference_number')
                ->label('Reference number')
                ->helperText('Stable identifier used to locate and cross-reference this document record.')
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->disabled($metadataLocked),
            TextInput::make('title')->required()->maxLength(255)->disabled($metadataLocked),
            Textarea::make('description')->rows(3)->columnSpanFull()->disabled($metadataLocked),
            Select::make('category_id')->relationship('category', 'name')->searchable()->preload()->required()->disabled($metadataLocked),
            Select::make('document_type_id')->relationship('documentType', 'name')->searchable()->preload()->required()->disabled($metadataLocked),
            Select::make('department_id')->relationship('department', 'name')->searchable()->preload()->required()->disabled($metadataLocked),
            Select::make('visibility')->options(DocumentVisibility::options())->required()->default(DocumentVisibility::Department->value)->disabled($metadataLocked),
            DatePicker::make('effective_date')->disabled($metadataLocked),
            DatePicker::make('expiry_date')->afterOrEqual('effective_date')->disabled($metadataLocked),
            DatePicker::make('next_review_date')->disabled($metadataLocked),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('Reference')->searchable()->sortable()->copyable(),
                TextColumn::make('title')->searchable()->sortable()->limit(55),
                TextColumn::make('category.name')->label('Category')->sortable()->toggleable(),
                TextColumn::make('documentType.name')->label('Document type')->sortable(),
                TextColumn::make('department.name')->label('Department')->sortable(),
                TextColumn::make('owner.name')->label('Owner')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('effective_date')->date()->placeholder('Not set')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')->date()->sortable()->placeholder('No expiry')
                    ->color(fn (Document $record) => $record->isExpired() ? 'danger' : null),
                TextColumn::make('next_review_date')->date()->placeholder('Not set')->toggleable(),
                TextColumn::make('workflow_status')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name')->searchable()->preload(),
                SelectFilter::make('document_type')->relationship('documentType', 'name')->searchable()->preload(),
                SelectFilter::make('department')->relationship('department', 'name')->searchable()->preload(),
                SelectFilter::make('visibility')->options(DocumentVisibility::options()),
                SelectFilter::make('workflow_status')->options(collect(DocumentWorkflowStatus::cases())->mapWithKeys(fn ($status) => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])->all()),
            ])
            ->recordActions([
                Action::make('manageVersions')
                    ->label('Versions / workflow')
                    ->url(fn (Document $record): string => static::getUrl('workflow', ['record' => $record]))
                    ->visible(fn (Document $record): bool => Auth::user()?->can('view', $record) ?? false),
                Action::make('versionHistory')
                    ->label('Version history')
                    ->icon('heroicon-o-clock')
                    ->url(fn (Document $record): string => static::getUrl('history', ['record' => $record]))
                    ->visible(fn (Document $record): bool => Auth::user()?->can('view', $record) ?? false),
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) return $query->whereRaw('1 = 0');

        return $query->with(['category', 'documentType', 'department', 'owner'])
            ->where(function (Builder $visible) use ($user) {
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
            'workflow' => ManageDocumentVersions::route('/{record}/workflow'),
            'history' => ManageDocumentVersionHistory::route('/{record}/version-history'),
        ];
    }
}
