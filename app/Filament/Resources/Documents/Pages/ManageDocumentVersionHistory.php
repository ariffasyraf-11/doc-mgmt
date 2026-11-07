<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentVersionIntegrityService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Gate;

class ManageDocumentVersionHistory extends Page
{
    use InteractsWithRecord;

    protected static string $resource = DocumentResource::class;

    protected string $view = 'filament.resources.documents.pages.manage-document-version-history';

    protected static ?string $title = 'Document version history';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadDocumentRelations($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('workflow')
                ->label('Back to workflow')
                ->icon('heroicon-o-arrow-left')
                ->url(DocumentResource::getUrl('workflow', ['record' => $this->getRecord()])),
        ];
    }

    public function verifyIntegrity(int $versionId, DocumentVersionIntegrityService $integrity): void
    {
        $document = $this->authorizedDocument();
        $version = $document->versions()->findOrFail($versionId);
        $check = $integrity->verify($document, $version, auth()->user());

        session()->flash('status', match ($check->status) {
            'verified' => 'File integrity verified: SHA-256 matches the original checksum.',
            'mismatch' => 'Integrity warning: the calculated SHA-256 does not match the stored checksum.',
            'missing' => 'Integrity check failed: the file is missing from private storage.',
            'unreadable' => 'Integrity check could not read the file from private storage.',
            default => 'Integrity check unavailable: no valid original SHA-256 checksum is recorded.',
        });

        $this->record = $this->loadDocumentRelations($document->refresh());
    }

    private function authorizedDocument(): Document
    {
        /** @var Document $document */
        $document = $this->getRecord();
        Gate::authorize('view', $document);

        return $document;
    }

    private function loadDocumentRelations(Document $document): Document
    {
        return $document->load([
            'category',
            'documentType',
            'versions.uploader',
            'versions.supersedes',
            'versions.integrityChecks.checker',
        ]);
    }

    protected function getViewData(): array
    {
        /** @var Document $document */
        $document = $this->getRecord();

        return [
            'document' => $document,
            'versions' => $document->versions->sortByDesc('version_number')->values(),
        ];
    }
}
