<?php

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Models\DocumentType;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Document Types';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Toggle::make('is_active')->label('Active')->default(true),

            Section::make('Expiry reminders')
                ->description('Configure when reminders are sent and who receives them for documents of this type.')
                ->schema([
                    CheckboxList::make('expiry_reminder_days')
                        ->label('Send before expiry')
                        ->options([
                            90 => '90 days before',
                            30 => '30 days before',
                            7 => '7 days before',
                            0 => 'On expiry date',
                        ])
                        ->default([90, 30, 7, 0])
                        ->columns(2),
                    CheckboxList::make('expiry_reminder_recipients')
                        ->label('Recipients')
                        ->options([
                            'owner' => 'Document owner',
                            'document_managers' => 'Administrators and document managers',
                            'assigned_reviewers' => 'Reviewers assigned to the latest review cycle',
                        ])
                        ->default(['owner'])
                        ->columns(2),
                ])->columns(2)->columnSpanFull(),

            Section::make('Next review reminders')
                ->description('Remind recipients before the next review date. This does not start a review cycle automatically.')
                ->schema([
                    CheckboxList::make('next_review_reminder_days')
                        ->label('Send before review date')
                        ->options([
                            30 => '30 days before',
                            7 => '7 days before',
                            0 => 'On review date',
                        ])
                        ->default([30, 7, 0])
                        ->columns(2),
                    CheckboxList::make('next_review_reminder_recipients')
                        ->label('Recipients')
                        ->options([
                            'owner' => 'Document owner',
                            'document_managers' => 'Administrators and document managers',
                            'assigned_reviewers' => 'Reviewers assigned to the latest review cycle',
                        ])
                        ->default(['owner', 'document_managers'])
                        ->columns(2),
                ])->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('description')->limit(70),
            IconColumn::make('is_active')->label('Active')->boolean(),
            TextColumn::make('updated_at')->dateTime()->sortable(),
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
            'index' => ListDocumentTypes::route('/'),
            'create' => CreateDocumentType::route('/create'),
            'edit' => EditDocumentType::route('/{record}/edit'),
        ];
    }
}
