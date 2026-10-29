<?php

namespace App\Filament\Resources\DocumentAccessGrants\Pages;

use App\Filament\Resources\DocumentAccessGrants\DocumentAccessResource;
use App\Models\Document;
use App\Models\DocumentAccess;
use App\Models\User;
use App\Services\DocumentAuditService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
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

        if (DocumentAccess::query()
            ->where('document_id', $document->id)
            ->where('user_id', $recipient->id)
            ->whereKeyNot($this->record->id)
            ->exists()) {
            throw ValidationException::withMessages(['user_id' => 'This user already has an access grant for the selected document.']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(DocumentAuditService::class)->record(Auth::id(), 'document.access_updated', $this->record->document, [
            'user_id' => $this->record->user_id,
            'access_level' => $this->record->access_level,
        ]);
    }
}
