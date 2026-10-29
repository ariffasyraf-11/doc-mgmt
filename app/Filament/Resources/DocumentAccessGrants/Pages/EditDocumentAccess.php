<?php

namespace App\Filament\Resources\DocumentAccessGrants\Pages;

use App\Filament\Resources\DocumentAccessGrants\DocumentAccessResource;
use App\Models\Document;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditDocumentAccess extends EditRecord
{
    protected static string $resource = DocumentAccessResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $document = Document::query()->findOrFail($data['document_id']);
        Gate::authorize('manageAccess', $document);

        $recipient = User::query()->findOrFail($data['user_id']);
        if (! $recipient->is_active) {
            throw ValidationException::withMessages(['user_id' => 'Access can only be granted to an active user.']);
        }

        return $data;
    }
}
