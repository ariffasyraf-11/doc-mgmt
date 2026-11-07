<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentVersionIntegrityService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Facades\Gate;

class ManageDocumentVersions extends Page
{
    use InteractsWithRecord;

    protected static string $resource = DocumentResource::class;

    protected string $view = 'filament.resources.documents.pages.manage-document-versions';

    protected static ?string $title = 'Document versions and workflow';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        Gate::authorize('view', $this->getRecord());

        $this->getRecord()->load([
            'category',
            'documentType',
            'department',
            'owner',
            'versions.uploader',
            'versions.supersedes',
            'versions.reviews.reviewer',
            'versions.integrityChecks.checker',
        ]);
    }

    public function verifyIntegrity(int $versionId, DocumentVersionIntegrityService $integrity): void
    {
        /** @var Document $document */
        $document = $this->getRecord();

        Gate::authorize('view', $document);

        /** @var DocumentVersion $version */
        $version = $document->versions()->findOrFail($versionId);

        $check = $integrity->verify($document, $version, auth()->user());

        session()->flash('status', match ($check->status) {
            'verified' => 'File integrity verified: SHA-256 matches the original checksum.',
            'mismatch' => 'Integrity warning: the calculated SHA-256 does not match the stored checksum.',
            'missing' => 'Integrity check failed: the file is missing from private storage.',
            'unreadable' => 'Integrity check could not read the file from private storage.',
            default => 'Integrity check unavailable: no valid original SHA-256 checksum is recorded.',
        });

        $document->load('versions.integrityChecks.checker');
    }

    protected function getViewData(): array
    {
        /** @var Document $document */
        $document = $this->getRecord();

        return [
            'document' => $document,
            'versions' => $document->versions->sortByDesc('version_number')->values(),
            'reviewers' => User::query()
                ->where('is_active', true)
                ->where('role', 'reviewer')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'canUpload' => auth()->user()->can('uploadVersion', $document),
            'canSubmit' => auth()->user()->can('submit', $document),
            'canPublish' => auth()->user()->can('publish', $document),
            'canArchive' => auth()->user()->can('archive', $document),
        ];
    }
}
