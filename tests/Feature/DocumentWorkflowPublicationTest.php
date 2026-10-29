<?php

namespace Tests\Feature;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\ReviewMode;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTypeReviewPolicy;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentWorkflowPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacement_does_not_replace_current_publication_until_publish_succeeds(): void
    {
        Storage::fake('private');

        $department = Department::query()->create(['name' => 'Operations', 'code' => 'OPS']);
        $category = Category::query()->create(['name' => 'Policy']);
        $type = DocumentType::query()->create(['name' => 'Internal Policy']);

        DocumentTypeReviewPolicy::query()->create([
            'document_type_id' => $type->id,
            'review_mode' => ReviewMode::Sequential,
            'required_reviewers' => 1,
            'owner_can_review' => false,
            'rejection_mode' => 'reject_cycle',
            'is_active' => true,
        ]);

        $owner = $this->makeUser($department->id, 'owner@example.test', UserRole::Employee);
        $reviewer = $this->makeUser($department->id, 'reviewer@example.test', UserRole::Reviewer);
        $manager = $this->makeUser($department->id, 'manager@example.test', UserRole::DocumentManager);

        $document = Document::query()->create([
            'reference_number' => 'DOC-REPLACE-001',
            'title' => 'Published policy',
            'category_id' => $category->id,
            'document_type_id' => $type->id,
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'visibility' => DocumentVisibility::Department,
            'workflow_status' => DocumentWorkflowStatus::Published,
        ]);

        Storage::disk('private')->put('1/old.pdf', 'old version');
        Storage::disk('private')->put('1/new.pdf', 'replacement version');

        $oldVersion = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => '1/old.pdf',
            'original_filename' => 'policy-v1.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
            'checksum' => hash('sha256', 'old version'),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
            'published_at' => now(),
        ]);

        $document->update(['current_published_version_id' => $oldVersion->id]);

        $newVersion = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 2,
            'file_path' => '1/new.pdf',
            'original_filename' => 'policy-v2.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 19,
            'checksum' => hash('sha256', 'replacement version'),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Draft,
            'supersedes_version_id' => $oldVersion->id,
        ]);

        $workflow = app(DocumentWorkflowService::class);
        $cycle = $workflow->submit($document->fresh(), $newVersion, $owner, [$reviewer->id]);

        $this->assertSame($oldVersion->id, $document->fresh()->current_published_version_id);
        $this->assertSame(DocumentWorkflowStatus::Published, $document->fresh()->workflow_status);
        $this->assertSame(DocumentVersionStatus::UnderReview, $newVersion->fresh()->status);

        $workflow->decide($cycle->reviews->first(), $reviewer, ReviewStatus::Approved, 'Reviewed.');
        $this->assertSame($oldVersion->id, $document->fresh()->current_published_version_id);
        $this->assertSame(DocumentWorkflowStatus::Published, $document->fresh()->workflow_status);

        $workflow->publish($document->fresh(), $newVersion->fresh(), $manager);

        $this->assertSame($newVersion->id, $document->fresh()->current_published_version_id);
        $this->assertSame(DocumentWorkflowStatus::Published, $document->fresh()->workflow_status);
        $this->assertSame(DocumentVersionStatus::Superseded, $oldVersion->fresh()->status);
        $this->assertSame(DocumentVersionStatus::Published, $newVersion->fresh()->status);
    }

    private function makeUser(int $departmentId, string $email, UserRole $role): User
    {
        return User::query()->create([
            'name' => ucfirst(strtok($email, '@')),
            'email' => $email,
            'password' => 'testing-password',
            'department_id' => $departmentId,
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
