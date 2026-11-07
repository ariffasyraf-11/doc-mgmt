<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentVersionReview;
use App\Services\DocumentWorkflowService;
use App\Services\DocumentVersionIntegrityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentWorkflowController
{
    public function verifyIntegrity(Request $request, Document $document, DocumentVersion $version, DocumentVersionIntegrityService $integrity): RedirectResponse
    {
        abort_unless($version->document_id === $document->id, 404);
        Gate::authorize('view', $document);

        $check = $integrity->verify($document, $version, $request->user());

        $message = match ($check->status) {
            'verified' => 'File integrity verified: SHA-256 matches the original checksum.',
            'mismatch' => 'Integrity warning: the calculated SHA-256 does not match the stored checksum.',
            'missing' => 'Integrity check failed: the file is missing from private storage.',
            'unreadable' => 'Integrity check could not read the file from private storage.',
            default => 'Integrity check unavailable: no valid original SHA-256 checksum is recorded.',
        };

        return back()->with('status', $message);
    }

    public function submit(Request $request, Document $document, DocumentVersion $version, DocumentWorkflowService $workflow): RedirectResponse
    {
        abort_unless($version->document_id === $document->id, 404);

        $validated = $request->validate([
            'reviewer_ids' => ['required', 'array', 'min:1'],
            'reviewer_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
        ]);

        $workflow->submit($document, $version, $request->user(), $validated['reviewer_ids']);

        return back()->with('status', 'Version submitted for review.');
    }

    public function decide(Request $request, Document $document, DocumentVersion $version, DocumentVersionReview $review, DocumentWorkflowService $workflow): RedirectResponse
    {
        abort_unless($version->document_id === $document->id && $review->document_version_id === $version->id, 404);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comments' => ['required_if:decision,rejected', 'nullable', 'string', 'max:5000'],
        ]);

        $workflow->decide($review, $request->user(), ReviewStatus::from($validated['decision']), $validated['comments'] ?? null);

        return back()->with('status', 'Review decision recorded.');
    }

    public function publish(Request $request, Document $document, DocumentVersion $version, DocumentWorkflowService $workflow): RedirectResponse
    {
        abort_unless($version->document_id === $document->id, 404);

        $workflow->publish($document, $version, $request->user());

        return back()->with('status', 'Approved version published.');
    }

    public function archive(Request $request, Document $document, DocumentWorkflowService $workflow): RedirectResponse
    {
        $workflow->archive($document, $request->user());

        return back()->with('status', 'Document archived. Historical versions remain retained.');
    }
}
