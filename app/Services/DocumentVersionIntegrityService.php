<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentVersionIntegrityCheck;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentVersionIntegrityService
{
    public function __construct(private readonly DocumentAuditService $audit) {}

    public function verify(Document $document, DocumentVersion $version, User $actor): DocumentVersionIntegrityCheck
    {
        abort_unless($version->document_id === $document->id, 404);

        $storedChecksum = $version->checksum;
        $calculatedChecksum = null;

        if (! Storage::disk('private')->exists($version->file_path)) {
            $status = 'missing';
        } elseif (! is_string($storedChecksum) || ! preg_match('/^[a-f0-9]{64}$/i', $storedChecksum)) {
            $status = 'checksum_unavailable';
        } else {
            $stream = Storage::disk('private')->readStream($version->file_path);

            if (! is_resource($stream)) {
                $status = 'unreadable';
            } else {
                try {
                    $context = hash_init('sha256');
                    $bytesHashed = hash_update_stream($context, $stream);

                    if ($bytesHashed === false) {
                        $status = 'unreadable';
                    } else {
                        $calculatedChecksum = hash_final($context);
                        $status = hash_equals(strtolower($storedChecksum), $calculatedChecksum)
                            ? 'verified'
                            : 'mismatch';
                    }
                } catch (\Throwable $exception) {
                    throw new RuntimeException('Unable to calculate the file SHA-256 checksum.', previous: $exception);
                } finally {
                    fclose($stream);
                }
            }
        }

        $check = DocumentVersionIntegrityCheck::query()->create([
            'document_version_id' => $version->id,
            'checked_by' => $actor->id,
            'stored_checksum' => $storedChecksum,
            'calculated_checksum' => $calculatedChecksum,
            'status' => $status,
            'checked_at' => now(),
        ]);

        $this->audit->record($actor->id, 'document.version_integrity_checked', $document, [
            'version_id' => $version->id,
            'version_number' => $version->version_number,
            'integrity_check_id' => $check->id,
            'status' => $status,
            'stored_checksum' => $storedChecksum,
            'calculated_checksum' => $calculatedChecksum,
        ]);

        return $check;
    }
}
