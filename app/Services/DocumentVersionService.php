<?php

namespace App\Services;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentWorkflowStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class DocumentVersionService
{
    public function __construct(private readonly DocumentAuditService $audit) {}

    public function upload(Document $document, UploadedFile $file, User $actor, ?string $changeNotes = null): DocumentVersion
    {
        if (! in_array($document->workflow_status, [
            DocumentWorkflowStatus::Draft,
            DocumentWorkflowStatus::Rejected,
            DocumentWorkflowStatus::Published,
        ], true)) {
            throw ValidationException::withMessages(['document' => 'A new version cannot be uploaded while this document is in an active review or approval state.']);
        }

        $path = $file->store((string) $document->id, 'private');

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['file' => 'The file could not be stored.']);
        }

        try {
            return DB::transaction(function () use ($document, $file, $actor, $changeNotes, $path) {
                $lockedDocument = Document::query()->lockForUpdate()->findOrFail($document->id);
                $nextVersionNumber = ((int) $lockedDocument->versions()->max('version_number')) + 1;
                $previousVersionId = $lockedDocument->current_published_version_id;
                $absolutePath = Storage::disk('private')->path($path);
                $checksum = hash_file('sha256', $absolutePath);

                if (! is_string($checksum)) {
                    throw ValidationException::withMessages(['file' => 'Unable to calculate the uploaded file checksum.']);
                }

                $version = $lockedDocument->versions()->create([
                    'version_number' => $nextVersionNumber,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                    'checksum' => $checksum,
                    'change_notes' => $changeNotes,
                    'uploaded_by' => $actor->id,
                    'status' => DocumentVersionStatus::Draft,
                    'supersedes_version_id' => $previousVersionId,
                ]);

                $this->audit->record($actor->id, 'document.version_uploaded', $lockedDocument, [
                    'version_id' => $version->id,
                    'version_number' => $version->version_number,
                ]);

                return $version;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }
    }
}
