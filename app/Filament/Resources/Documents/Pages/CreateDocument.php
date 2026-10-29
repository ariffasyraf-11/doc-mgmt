<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $data['owner_id'] = $user->id;
        $data['workflow_status'] = 'draft';
        $data['reference_number'] = filled($data['reference_number'] ?? null)
            ? $data['reference_number']
            : 'DOC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        if (! $user->isAdministrator() && ! $user->isDocumentManager()) {
            $data['department_id'] = $user->department_id;
        }

        return $data;
    }
}
