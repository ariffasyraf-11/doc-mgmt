<?php

namespace App\Filament\Resources\DocumentAccessGrants;

use App\Filament\Resources\DocumentAccessGrants\Pages\CreateDocumentAccess;
use App\Filament\Resources\DocumentAccessGrants\Pages\EditDocumentAccess;
use App\Filament\Resources\DocumentAccessGrants\Pages\ListDocumentAccess;
use App\Models\Document;
use App\Models\DocumentAccess;
use App\Models\User;
use App\Services\DocumentAuditService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentAccessResource extends Resource
{
    protected static ?string $model = DocumentAccess::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Access Grants';
    protected static ?string $modelLabel = 'access grant';
    protected static ?string $pluralModelLabel = 'access grants';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('document_id')
                ->relationship('document', 'title', modifyQueryUsing: fn (Builder $query): Builder => self::manageableDocuments($query))
                ->searchable()->preload()->required(),
            Select::make('user_id')
                ->relationship('user', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
                ->searchable()->preload()->required(),
            Select::make('access_level')->options([
                'view' => 'View metadata',
                'download' => 'View and download',
                'manage' => 'View, download and manage access',
            ])->required()->default('view'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('document.reference_number')->label('Reference')->searchable(),
            TextColumn::make('document.title')->label('Document')->searchable()->limit(50),
            TextColumn::make('user.name')->label('Granted to')->searchable(),
            TextColumn::make('access_level')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'view' => 'View',
                'download' => 'View and download',
                'manage' => 'Manage access',
                default => (string) $state,
            }),
            TextColumn::make('grantedBy.name')->label('Granted by'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->recordActions([
            EditAction::make(),
            DeleteAction::make()->after(function (DocumentAccess $record): void {
                app(DocumentAuditService::class)->record(Auth::id(), 'document.access_revoked', $record->document, [
                    'user_id' => $record->user_id,
                    'access_level' => $record->access_level,
                ]);
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        if (! $user instanceof User) return $query->whereRaw('1 = 0');

        return $query->whereHas('document', fn (Builder $documents) => self::manageableDocuments($documents, $user));
    }

    private static function manageableDocuments(Builder $query, ?User $user = null): Builder
    {
        $user ??= Auth::user();
        if (! $user instanceof User) return $query->whereRaw('1 = 0');

        return $query->where(function (Builder $documents) use ($user) {
            $documents->where('owner_id', $user->id);
            if ($user->isAdministrator() || $user->isDocumentManager()) {
                $documents->orWhereIn('visibility', ['organization', 'department']);
            }
            $documents->orWhereHas('accessGrants', fn (Builder $grants) => $grants
                ->where('user_id', $user->id)->where('access_level', 'manage'));
        });
    }

    public static function canViewAny(): bool { return Auth::check() && Auth::user()->is_active; }
    public static function canCreate(): bool { return Auth::check() && Auth::user()->is_active; }
    public static function canEdit(Model $record): bool { return Auth::user()?->can('manageAccess', $record->document) ?? false; }
    public static function canDelete(Model $record): bool { return Auth::user()?->can('manageAccess', $record->document) ?? false; }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentAccess::route('/'),
            'create' => CreateDocumentAccess::route('/create'),
            'edit' => EditDocumentAccess::route('/{record}/edit'),
        ];
    }
}
