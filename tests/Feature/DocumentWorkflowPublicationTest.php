<?php

namespace Tests\Feature;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\ReviewMode;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Documents\Pages\ManageDocumentVersions;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentTypeReviewPolicy;
use App\Models\DocumentVersion;
use App\Models\DocumentVersionReview;
use App\Models\User;
use App\Services\DocumentWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_document_version_workflow_runs_through_filament_livewire_actions(): void
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
            'reference_number' => 'DOC-LIVEWIRE-001',
            'title' => 'Livewire workflow policy',
            'category_id' => $category->id,
            'document_type_id' => $type->id,
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'visibility' => DocumentVisibility::Department,
            'workflow_status' => DocumentWorkflowStatus::Draft,
        ]);

        Livewire::actingAs($owner)
            ->test(ManageDocumentVersions::class, ['record' => $document->id])
            ->set('data.file', [UploadedFile::fake()->create('policy.pdf', 50, 'application/pdf')])
            ->call('uploadVersion')
            ->assertHasNoErrors();

        $version = DocumentVersion::query()->where('document_id', $document->id)->firstOrFail();

        Livewire::actingAs($owner)
            ->test(ManageDocumentVersions::class, ['record' => $document->id])
            ->set("reviewerIds.{$version->id}", [$reviewer->id])
            ->call('submitVersion', $version->id)
            ->assertHasNoErrors();

        $review = DocumentVersionReview::query()->where('document_version_id', $version->id)->firstOrFail();

        Livewire::actingAs($reviewer)
            ->test(ManageDocumentVersions::class, ['record' => $document->id])
            ->call('decideReview', $version->id, $review->id, 'rejected')
            ->assertHasErrors('comments')
            ->set("decisionComments.{$review->id}", 'Reviewed and approved.')
            ->call('decideReview', $version->id, $review->id, 'approved')
            ->assertHasNoErrors();

        Livewire::actingAs($manager)
            ->test(ManageDocumentVersions::class, ['record' => $document->id])
            ->call('publishVersion', $version->id)
            ->assertHasNoErrors()
            ->call('archiveDocument')
            ->assertHasNoErrors();

        $this->assertSame(DocumentVersionStatus::Published, $version->fresh()->status);
        $this->assertSame(DocumentWorkflowStatus::Archived, $document->fresh()->workflow_status);
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
