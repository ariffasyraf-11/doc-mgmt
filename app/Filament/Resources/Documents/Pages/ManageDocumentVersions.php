<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentVersionService;
use App\Services\DocumentWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ManageDocumentVersions extends Page
{
    use InteractsWithRecord;

    public ?array $data = [];

    /** @var array<int, array<int, int|string>> */
    public array $reviewerIds = [];

    /** @var array<int, string> */
    public array $decisionComments = [];

    protected static string $resource = DocumentResource::class;

    protected string $view = 'filament.resources.documents.pages.manage-document-versions';

    protected static ?string $title = 'Document versions and workflow';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file')
                    ->label('Document file')
                    ->helperText('PDF, Word, Excel, PowerPoint, or TXT. Maximum 20 MB.')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                    ])
                    ->maxSize(20480)
                    ->maxFiles(1)
                    ->storeFiles(false)
                    ->required()
                    ->rules(['mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt']),
                Textarea::make('changeNotes')
                    ->label('Change notes')
                    ->placeholder('Describe what changed in this version...')
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        Gate::authorize('view', $this->getRecord());
        $this->record = $this->loadDocumentRelations($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('versionHistory')
                ->label('Version history')
                ->icon('heroicon-o-clock')
                ->url(DocumentResource::getUrl('history', ['record' => $this->getRecord()])),
        ];
    }

    public function uploadVersion(DocumentVersionService $versions): void
    {
        $document = $this->authorizedDocument();
        Gate::authorize('uploadVersion', $document);

        $validated = $this->form->getState();
        $uploadedFile = Arr::first(Arr::wrap($validated['file']));

        $version = $versions->upload($document, $uploadedFile, auth()->user(), $validated['changeNotes'] ?? null);

        $this->resetValidation();
        $this->form->fill();
        session()->flash('status', 'Uploaded version '.$version->version_number.'. It remains a draft until submitted for review.');
        $this->refreshDocument();
    }

    public function submitVersion(int $versionId, DocumentWorkflowService $workflow): void
    {
        $document = $this->authorizedDocument();
        Gate::authorize('submit', $document);

        $validated = $this->validate([
            "reviewerIds.{$versionId}" => ['required', 'array', 'min:1'],
            "reviewerIds.{$versionId}.*" => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $version = $document->versions()->findOrFail($versionId);
        $workflow->submit($document, $version, auth()->user(), $validated['reviewerIds'][$versionId]);

        $this->resetValidation();
        unset($this->reviewerIds[$versionId]);
        session()->flash('status', 'Version submitted for review.');
        $this->refreshDocument();
    }

    public function decideReview(int $versionId, int $reviewId, string $decision, DocumentWorkflowService $workflow): void
    {
        $document = $this->authorizedDocument();
        $version = $document->versions()->findOrFail($versionId);
        $review = $version->reviews()->findOrFail($reviewId);

        $validated = Validator::make([
            'decision' => $decision,
            'comments' => $this->decisionComments[$reviewId] ?? null,
        ], [
            'decision' => ['required', 'in:approved,rejected'],
            'comments' => ['required_if:decision,rejected', 'nullable', 'string', 'max:5000'],
        ])->validate();

        $workflow->decide(
            $review,
            auth()->user(),
            ReviewStatus::from($validated['decision']),
            $validated['comments'] ?? null,
        );

        $this->resetValidation();
        unset($this->decisionComments[$reviewId]);
        session()->flash('status', 'Review decision recorded.');
        $this->refreshDocument();
    }

    public function publishVersion(int $versionId, DocumentWorkflowService $workflow): void
    {
        $document = $this->authorizedDocument();
        $version = $document->versions()->findOrFail($versionId);

        $workflow->publish($document, $version, auth()->user());

        $this->resetValidation();
        session()->flash('status', 'Approved version published.');
        $this->refreshDocument();
    }

    public function archiveDocument(DocumentWorkflowService $workflow): void
    {
        $document = $this->authorizedDocument();

        $workflow->archive($document, auth()->user());

        $this->resetValidation();
        session()->flash('status', 'Document archived. Historical versions remain retained.');
        $this->refreshDocument();
    }

    private function authorizedDocument(): Document
    {
        /** @var Document $document */
        $document = $this->getRecord();
        Gate::authorize('view', $document);

        return $document;
    }

    private function refreshDocument(): void
    {
        $this->record = $this->loadDocumentRelations($this->getRecord()->refresh());
    }

    private function loadDocumentRelations(Document $document): Document
    {
        return $document->load([
            'category',
            'documentType',
            'department',
            'owner',
            'versions.uploader',
            'versions.supersedes',
            'versions.reviews.reviewer',
        ]);
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
