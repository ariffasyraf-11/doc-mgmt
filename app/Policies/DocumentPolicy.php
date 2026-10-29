<?php

namespace App\Policies;

use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool { return $user->is_active; }

    public function view(User $user, Document $document): bool
    {
        if (! $user->is_active) return false;
        if ($document->owner_id === $user->id) return true;

        if (in_array($document->visibility, [DocumentVisibility::Restricted, DocumentVisibility::Confidential], true)) {
            return $document->accessGrants()->where('user_id', $user->id)->exists()
                || $this->isAssignedActiveReviewer($user, $document);
        }

        if (in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)) return true;

        return match ($document->visibility) {
            DocumentVisibility::Organization => true,
            DocumentVisibility::Department => $user->department_id !== null && $user->department_id === $document->department_id,
            DocumentVisibility::Restricted, DocumentVisibility::Confidential => false,
        };
    }

    public function create(User $user): bool { return $user->is_active; }

    public function update(User $user, Document $document): bool
    {
        if (! $user->is_active) return false;

        if (in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)) {
            return in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)
                || $document->owner_id === $user->id
                || $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists();
        }

        return $document->owner_id === $user->id
            && in_array($document->workflow_status, [DocumentWorkflowStatus::Draft, DocumentWorkflowStatus::Rejected], true);
    }

    public function uploadVersion(User $user, Document $document): bool
    {
        if (! $user->is_active) return false;

        if ($document->owner_id === $user->id) {
            return in_array($document->workflow_status, [
                DocumentWorkflowStatus::Draft, DocumentWorkflowStatus::Rejected, DocumentWorkflowStatus::Published,
            ], true);
        }

        return in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)
            && (in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)
                || $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists());
    }

    public function submit(User $user, Document $document): bool
    {
        return $user->is_active
            && ($document->owner_id === $user->id || in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true))
            && (in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)
                || $document->owner_id === $user->id
                || $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists())
            && in_array($document->workflow_status, [
                DocumentWorkflowStatus::Draft, DocumentWorkflowStatus::Rejected, DocumentWorkflowStatus::Published,
            ], true);
    }

    public function publish(User $user, Document $document): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)
            && (in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)
                || $document->owner_id === $user->id
                || $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists());
    }

    public function archive(User $user, Document $document): bool
    {
        return $user->is_active
            && in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)
            && (in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)
                || $document->owner_id === $user->id
                || $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists())
            && $document->workflow_status === DocumentWorkflowStatus::Published;
    }

    public function manageAccess(User $user, Document $document): bool
    {
        if (! $user->is_active) return false;
        if ($document->owner_id === $user->id) return true;

        if (in_array($user->role, [UserRole::Administrator, UserRole::DocumentManager], true)
            && in_array($document->visibility, [DocumentVisibility::Organization, DocumentVisibility::Department], true)) {
            return true;
        }

        return $document->accessGrants()->where('user_id', $user->id)->where('access_level', 'manage')->exists();
    }

    public function download(User $user, DocumentVersion $version): bool
    {
        return $user->can('download', $version);
    }

    private function isAssignedActiveReviewer(User $user, Document $document): bool
    {
        return $document->versions()->whereHas('reviews', fn ($reviews) => $reviews
            ->where('reviewer_id', $user->id)
            ->whereHas('cycle', fn ($cycles) => $cycles->where('status', 'in_progress')))
            ->exists();
    }
}
