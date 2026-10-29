<?php

namespace App\Filament\Resources\DocumentAccessGrants\Pages;

use App\Filament\Resources\DocumentAccessGrants\DocumentAccessResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocumentAccess extends CreateRecord
{
    protected static string $resource = DocumentAccessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['granted_by'] = Auth::id();

        return $data;
    }
}
