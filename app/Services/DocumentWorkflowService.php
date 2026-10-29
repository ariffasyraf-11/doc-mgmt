<?php

namespace App\Services;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\ReviewCycleStatus;
use App\Enums\ReviewMode;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentReviewCycle;
use App\Models\DocumentTypeReviewPolicy;
use App\Models\DocumentVersion;
use App\Models\DocumentVersionReview;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentWorkflowService
{
    public function __construct(private readonly DocumentAuditService $audit) {}

    /** @param array<int> $reviewerIds */
    public function submit(Document $document, DocumentVersion $version, User $actor, array $reviewerIds): DocumentReviewCycle
    {
        if (! $actor->can('submit', $document)) throw new AuthorizationException('You are not allowed to submit this document.');
        if ($version->document_id !== $document->id) throw ValidationException::withMessages(['version' => 'The version does not belong to this document.']);
        if (! in_array($version->status, [DocumentVersionStatus::Draft, DocumentVersionStatus::Rejected], true)) {
            throw ValidationException::withMessages(['version' => 'Only a draft or rejected version can be submitted.']);
        }
        if (! Storage::disk('private')->exists($version->file_path)) throw ValidationException::withMessages(['file' => 'The file is missing from private storage.']);

        $policy = $document->documentType?->activeReviewPolicy;
        if (! $policy instanceof DocumentTypeReviewPolicy) throw ValidationException::withMessages(['document_type_id' => 'An active review policy is required.']);

        $reviewerIds = array_values(array_unique(array_map('intval', $reviewerIds)));
        if (count($reviewerIds) < $policy->required_reviewers) throw ValidationException::withMessages(['reviewers' => 'Not enough reviewers are assigned for this policy.']);
        if (! $policy->owner_can_review && in_array($document->owner_id, $reviewerIds, true)) {
            throw ValidationException::withMessages(['reviewers' => 'The owner cannot review their own submission.']);
        }

        $reviewers = User::query()
            ->whereIn('id', $reviewerIds)
            ->where('is_active', true)
            ->where('role', UserRole::Reviewer->value)
            ->get();

        if ($reviewers->count() !== count($reviewerIds)) {
            throw ValidationException::withMessages(['reviewers' => 'Every assigned reviewer must be an active user with the Reviewer role.']);
        }

        return DB::transaction(function () use ($document, $version, $actor, $reviewerIds, $reviewers, $policy) {
            $lockedDocument = Document::query()->lockForUpdate()->findOrFail($document->id);
            $lockedVersion = DocumentVersion::query()->lockForUpdate()->findOrFail($version->id);

            if (! in_array($lockedDocument->workflow_status, [
                DocumentWorkflowStatus::Draft, DocumentWorkflowStatus::Rejected, DocumentWorkflowStatus::Published,
            ], true) || ! in_array($lockedVersion->status, [DocumentVersionStatus::Draft, DocumentVersionStatus::Rejected], true)) {
                throw ValidationException::withMessages(['document' => 'The document or version is no longer eligible for submission.']);
            }

            if ($lockedDocument->reviewCycles()->whereIn('status', [ReviewCycleStatus::Pending->value, ReviewCycleStatus::InProgress->value])->exists()) {
                throw ValidationException::withMessages(['document' => 'Another review cycle is already in progress for this document.']);
            }

            $cycle = DocumentReviewCycle::query()->create([
                'document_id' => $lockedDocument->id,
                'document_version_id' => $lockedVersion->id,
                'policy_snapshot' => [
                    'review_mode' => $policy->review_mode->value,
                    'required_reviewers' => $policy->required_reviewers,
                    'owner_can_review' => $policy->owner_can_review,
                    'rejection_mode' => $policy->rejection_mode,
                    'reviewer_ids' => $reviewerIds,
                ],
                'status' => ReviewCycleStatus::InProgress,
                'started_at' => now(),
            ]);

            foreach ($reviewers as $index => $reviewer) {
                DocumentVersionReview::query()->create([
                    'review_cycle_id' => $cycle->id,
                    'document_version_id' => $lockedVersion->id,
                    'reviewer_id' => $reviewer->id,
                    'sequence_number' => $index + 1,
                    'status' => ReviewStatus::Pending,
                ]);
            }

            $lockedVersion->update(['status' => DocumentVersionStatus::UnderReview, 'submitted_at' => now()]);
            if ($lockedDocument->current_published_version_id === null) {
                $lockedDocument->update(['workflow_status' => DocumentWorkflowStatus::UnderReview]);
            }

            $this->audit->record($actor->id, 'document.submitted_for_review', $lockedDocument, [
                'version_id' => $lockedVersion->id, 'review_cycle_id' => $cycle->id, 'reviewer_ids' => $reviewerIds,
            ]);

            return $cycle->load('reviews');
        });
    }

    public function decide(DocumentVersionReview $review, User $actor, ReviewStatus $decision, ?string $comments = null): DocumentReviewCycle
    {
        if (! in_array($decision, [ReviewStatus::Approved, ReviewStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'A review decision must be approved or rejected.']);
        }
        if ($review->reviewer_id !== $actor->id || ! $actor->is_active || ! $actor->isReviewer()) {
            throw new AuthorizationException('Only the assigned active reviewer can decide this review.');
        }

        return DB::transaction(function () use ($review, $actor, $decision, $comments) {
            $review = DocumentVersionReview::query()->lockForUpdate()->findOrFail($review->id);
            $cycle = DocumentReviewCycle::query()->lockForUpdate()->findOrFail($review->review_cycle_id);
            $version = DocumentVersion::query()->lockForUpdate()->findOrFail($review->document_version_id);
            $document = Document::query()->lockForUpdate()->findOrFail($version->document_id);

            if ($review->status !== ReviewStatus::Pending || $cycle->status !== ReviewCycleStatus::InProgress) {
                throw ValidationException::withMessages(['review' => 'This review cycle is no longer accepting decisions.']);
            }
            if (ReviewMode::from($cycle->policy_snapshot['review_mode']) === ReviewMode::Sequential) {
                $earlierPending = $cycle->reviews()->where('sequence_number', '<', $review->sequence_number)
                    ->where('status', '!=', ReviewStatus::Approved->value)->exists();
                if ($earlierPending) throw ValidationException::withMessages(['review' => 'Earlier reviewers must approve first.']);
            }

            $review->update(['status' => $decision, 'decision' => $decision->value, 'comments' => $comments, 'decided_at' => now()]);

            if ($decision === ReviewStatus::Rejected) {
                $cycle->update(['status' => ReviewCycleStatus::Rejected, 'completed_at' => now()]);
                $version->update(['status' => DocumentVersionStatus::Rejected]);
                if ($document->current_published_version_id === null) $document->update(['workflow_status' => DocumentWorkflowStatus::Rejected]);
            } elseif (! $cycle->reviews()->where('status', ReviewStatus::Pending->value)->exists()) {
                $cycle->update(['status' => ReviewCycleStatus::Approved, 'completed_at' => now()]);
                $version->update(['status' => DocumentVersionStatus::Approved, 'approved_at' => now()]);
                if ($document->current_published_version_id === null) $document->update(['workflow_status' => DocumentWorkflowStatus::Approved]);
            }

            $this->audit->record($actor->id, 'document.review_decided', $document, [
                'version_id' => $version->id, 'review_cycle_id' => $cycle->id,
                'review_id' => $review->id, 'decision' => $decision->value,
            ]);
            return $cycle->fresh('reviews');
        });
    }

    public function publish(Document $document, DocumentVersion $version, User $actor): void
    {
        if (! $actor->can('publish', $document)) throw new AuthorizationException('You are not allowed to publish this document.');
        if ($version->document_id !== $document->id || $version->status !== DocumentVersionStatus::Approved) {
            throw ValidationException::withMessages(['version' => 'Only an approved version can be published.']);
        }

        DB::transaction(function () use ($document, $version, $actor) {
            $document = Document::query()->lockForUpdate()->findOrFail($document->id);
            $version = DocumentVersion::query()->lockForUpdate()->findOrFail($version->id);
            if ($version->document_id !== $document->id || $version->status !== DocumentVersionStatus::Approved) {
                throw ValidationException::withMessages(['version' => 'The version is no longer eligible for publication.']);
            }

            $previousVersionId = $document->current_published_version_id;
            if ($previousVersionId !== null && $previousVersionId !== $version->id) {
                DocumentVersion::query()->whereKey($previousVersionId)->where('document_id', $document->id)
                    ->update(['status' => DocumentVersionStatus::Superseded->value]);
            }
            $version->update(['status' => DocumentVersionStatus::Published, 'published_at' => now()]);
            $document->update(['current_published_version_id' => $version->id, 'workflow_status' => DocumentWorkflowStatus::Published]);
            $this->audit->record($actor->id, 'document.version_published', $document, [
                'version_id' => $version->id, 'previous_version_id' => $previousVersionId,
            ]);
        });
    }

    public function archive(Document $document, User $actor): void
    {
        if (! $actor->can('archive', $document)) throw new AuthorizationException('You are not allowed to archive this document.');

        DB::transaction(function () use ($document, $actor) {
            $lockedDocument = Document::query()->lockForUpdate()->findOrFail($document->id);
            if ($lockedDocument->workflow_status !== DocumentWorkflowStatus::Published) {
                throw ValidationException::withMessages(['document' => 'Only a published document can be archived.']);
            }

            $lockedDocument->update(['workflow_status' => DocumentWorkflowStatus::Archived]);
            $this->audit->record($actor->id, 'document.archived', $lockedDocument, [
                'current_published_version_id' => $lockedDocument->current_published_version_id,
            ]);
        });
    }
}
