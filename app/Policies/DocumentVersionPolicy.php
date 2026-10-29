<?php

namespace App\Policies;

use App\Enums\DocumentWorkflowStatus;
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

        return $user->is_active
            && $user->can('view', $document)
            && $document->workflow_status !== DocumentWorkflowStatus::Archived
            && $document->current_published_version_id === $version->id;
    }
}
