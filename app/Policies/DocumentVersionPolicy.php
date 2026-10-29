<?php

namespace App\Policies;

use App\Enums\DocumentWorkflowStatus;
use App\Enums\ReviewCycleStatus;
use App\Enums\DocumentVisibility;
use App\Models\DocumentVersion;
use App\Models\User;

class DocumentVersionPolicy
{
    public function view(User $user, DocumentVersion $version): bool
    {
        return $user->is_active && $user->can('view', $version->document);
    }

    public function download(User $user, DocumentVersion $version): bool
    {
        $document = $version->document;

        if (! $user->is_active || ! $user->can('view', $document)) {
            return false;
        }

        // Owners retain access to their drafts and historical files.
        if ($document->owner_id === $user->id) {
            return true;
        }

        if ($document->workflow_status === DocumentWorkflowStatus::Archived) {
            return $document->accessGrants()
                ->where('user_id', $user->id)
                ->whereIn('access_level', ['download', 'manage'])
                ->exists()
                || $user->can('manageAccess', $document);
        }

        $isCurrentPublishedVersion = $document->current_published_version_id === $version->id;

        if ($isCurrentPublishedVersion) {
            if (in_array($document->visibility, [DocumentVisibility::Restricted, DocumentVisibility::Confidential], true)) {
                return $document->accessGrants()
                    ->where('user_id', $user->id)
                    ->whereIn('access_level', ['download', 'manage'])
                    ->exists();
            }

            return true;
        }

        if ($user->can('manageAccess', $document)) {
            return true;
        }

        return $version->reviews()
            ->where('reviewer_id', $user->id)
            ->whereHas('cycle', fn ($query) => $query->where('status', ReviewCycleStatus::InProgress->value))
            ->exists();
    }
}
