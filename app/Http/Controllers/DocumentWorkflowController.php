<?php

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentVersionReview;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentWorkflowController
{
    public function show(Request $request, Document $document)
    {
        Gate::authorize('view', $document);

        $document->load(['versions.reviews.reviewer']);
        $document->setRelation('versions', $document->versions->sortByDesc('version_number')->values());

        $reviewers = User::query()
            ->where('is_active', true)
            ->where('role', 'reviewer')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('documents.versions.create', [
            'document' => $document,
            'reviewers' => $reviewers,
            'canUpload' => $request->user()->can('uploadVersion', $document),
            'canSubmit' => $request->user()->can('submit', $document),
            'canPublish' => $request->user()->can('publish', $document),
            'canArchive' => $request->user()->can('archive', $document),
        ]);
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
