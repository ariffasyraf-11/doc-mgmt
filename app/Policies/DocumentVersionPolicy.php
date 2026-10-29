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

        if (! $user->is_active || ! $user->can('view', $document) || $document->workflow_status === DocumentWorkflowStatus::Archived) {
            return false;
        }

        $isCurrentPublishedVersion = $document->current_published_version_id === $version->id;

        if ($isCurrentPublishedVersion) {
            if (in_array($document->visibility, [DocumentVisibility::Restricted, DocumentVisibility::Confidential], true)
                && $document->owner_id !== $user->id) {
                return $document->accessGrants()
                    ->where('user_id', $user->id)
                    ->whereIn('access_level', ['download', 'manage'])
                    ->exists();
            }

            return true;
        }

        return $version->reviews()
            ->where('reviewer_id', $user->id)
            ->whereHas('cycle', fn ($query) => $query->where('status', ReviewCycleStatus::InProgress->value))
            ->exists();
    }
}
