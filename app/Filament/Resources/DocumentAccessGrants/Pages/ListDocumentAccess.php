<?php

namespace App\Filament\Resources\DocumentAccessGrants\Pages;

use App\Filament\Resources\DocumentAccessGrants\DocumentAccessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocumentAccess extends ListRecords
{
    protected static string $resource = DocumentAccessResource::class;

    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
