<?php

namespace App\Filament\Resources\DocumentAccessGrants\Pages;

use App\Filament\Resources\DocumentAccessGrants\DocumentAccessResource;
use App\Models\Document;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateDocumentAccess extends CreateRecord
{
    protected static string $resource = DocumentAccessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $document = Document::query()->findOrFail($data['document_id']);
        Gate::authorize('manageAccess', $document);

        $recipient = User::query()->findOrFail($data['user_id']);
        if (! $recipient->is_active) {
            throw ValidationException::withMessages(['user_id' => 'Access can only be granted to an active user.']);
        }

        $data['granted_by'] = Auth::id();

        return $data;
    }
}
