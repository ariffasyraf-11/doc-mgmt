<?php

namespace Tests\Feature;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAccess;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Policies\DocumentVersionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentDownloadPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_access_grant_does_not_allow_file_download(): void
    {
        $department = Department::query()->create(['name' => 'Operations', 'code' => 'OPS']);
        $category = Category::query()->create(['name' => 'General']);
        $type = DocumentType::query()->create(['name' => 'Internal Document']);
        $owner = $this->makeUser($department->id, 'owner@example.test');
        $reader = $this->makeUser($department->id, 'reader@example.test');

        $document = Document::query()->create([
            'reference_number' => 'DOC-DOWNLOAD-001',
            'title' => 'Restricted document',
            'category_id' => $category->id,
            'document_type_id' => $type->id,
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'visibility' => DocumentVisibility::Restricted,
            'workflow_status' => DocumentWorkflowStatus::Published,
        ]);

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => 'private.pdf',
            'original_filename' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'checksum' => hash('sha256', 'private'),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
            'published_at' => now(),
        ]);
        $document->update(['current_published_version_id' => $version->id]);

        $grant = DocumentAccess::query()->create([
            'document_id' => $document->id,
            'user_id' => $reader->id,
            'access_level' => 'view',
        ]);

        $policy = app(DocumentVersionPolicy::class);
        $this->assertTrue($reader->can('view', $document));
        $this->assertFalse($policy->download($reader, $version));
        $this->assertTrue($policy->download($owner, $version));

        $draft = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 2,
            'file_path' => 'draft.pdf',
            'original_filename' => 'draft.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 5,
            'checksum' => hash('sha256', 'draft'),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Draft,
        ]);
        $this->assertTrue($policy->download($owner, $draft));

        $grant->update(['access_level' => 'download']);
        $this->assertTrue($policy->download($reader, $version));
    }

    private function makeUser(int $departmentId, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst(strtok($email, '@')),
            'email' => $email,
            'password' => 'testing-password',
            'department_id' => $departmentId,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
    }
}
