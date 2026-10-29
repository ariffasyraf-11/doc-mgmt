<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\DocumentVersion;
use App\Services\DocumentVersionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController
{
    public function create(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeUpload($request, $document);

        return redirect()->route('documents.workflow.show', $document);
    }

    public function store(Request $request, Document $document, DocumentVersionService $versions): RedirectResponse
    {
        $this->authorizeUpload($request, $document);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt'],
            'change_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $version = $versions->upload($document, $validated['file'], $request->user(), $validated['change_notes'] ?? null);

        return redirect()->route('documents.workflow.show', $document)
            ->with('status', 'Uploaded version '.$version->version_number.'. It remains a draft until submitted for review.');
    }

    public function download(Request $request, Document $document, DocumentVersion $version): StreamedResponse
    {
        abort_unless($version->document_id === $document->id, 404);

        try {
            if (! $request->user()->can('download', $version)) {
                throw new AuthorizationException();
            }
        } catch (AuthorizationException $exception) {
            DocumentDownload::query()->create([
                'document_version_id' => $version->id,
                'user_id' => $request->user()?->id,
                'outcome' => 'denied',
                'created_at' => now(),
            ]);

            throw $exception;
        }

        if (! Storage::disk('private')->exists($version->file_path)) {
            DocumentDownload::query()->create([
                'document_version_id' => $version->id,
                'user_id' => $request->user()->id,
                'outcome' => 'missing_file',
                'created_at' => now(),
            ]);

            abort(404, 'The document file is not available.');
        }

        DocumentDownload::query()->create([
            'document_version_id' => $version->id,
            'user_id' => $request->user()->id,
            'outcome' => 'success',
            'created_at' => now(),
        ]);

        return Storage::disk('private')->download($version->file_path, $version->original_filename);
    }

    private function authorizeUpload(Request $request, Document $document): void
    {
        if (! $request->user()?->can('uploadVersion', $document)) {
            throw new AuthorizationException();
        }
    }
}
