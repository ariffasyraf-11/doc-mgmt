<?php

namespace Tests\Feature;

use App\Enums\DocumentVersionStatus;
use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_document_reference_metadata_and_file_version_history(): void
    {
        [$owner, $document] = $this->makeDocument();

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => 'private/1/policy.pdf',
            'original_filename' => 'policy-final.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'checksum' => hash('sha256', 'policy-file-content'),
            'change_notes' => 'Initial approved issue',
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
            'published_at' => now(),
        ]);

        $document->update(['current_published_version_id' => $version->id]);

        $response = $this->actingAs($owner)->get(route('documents.workflow.show', $document));

        $response->assertOk()
            ->assertSee('Document metadata / reference')
            ->assertSee('REF-POLICY-001')
            ->assertSee('Corporate Policy')
            ->assertSee('Operations')
            ->assertSee('File version history')
            ->assertSee('policy-final.pdf')
            ->assertSee('application/pdf')
            ->assertSee('2,048 bytes')
            ->assertSee(hash('sha256', 'policy-file-content'))
            ->assertSee('Initial approved issue')
            ->assertSee('Current published version');
    }

    public function test_unauthorized_user_cannot_view_document_metadata_or_version_history(): void
    {
        [, $document] = $this->makeDocument();

        $other = User::query()->create([
            'name' => 'Other Employee',
            'email' => 'other@example.test',
            'password' => 'testing-password',
            'department_id' => null,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->actingAs($other)
            ->get(route('documents.workflow.show', $document))
            ->assertForbidden();
    }

    public function test_document_viewer_can_verify_a_version_sha256_and_history_is_recorded(): void
    {
        Storage::fake('private');
        [$owner, $document] = $this->makeDocument();
        $content = 'original policy file';
        $path = '1/policy.pdf';
        Storage::disk('private')->put($path, $content);

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => $path,
            'original_filename' => 'policy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
        ]);

        $this->actingAs($owner)
            ->post(route('documents.versions.verify-integrity', [$document, $version]))
            ->assertRedirect()
            ->assertSessionHas('status', 'File integrity verified: SHA-256 matches the original checksum.');

        $this->assertDatabaseHas('document_version_integrity_checks', [
            'document_version_id' => $version->id,
            'checked_by' => $owner->id,
            'stored_checksum' => hash('sha256', $content),
            'calculated_checksum' => hash('sha256', $content),
            'status' => 'verified',
        ]);

        $this->assertDatabaseHas('document_audit_logs', [
            'action' => 'document.version_integrity_checked',
            'subject_type' => $document->getMorphClass(),
            'subject_id' => $document->id,
        ]);

        $this->actingAs($owner)
            ->get(route('documents.workflow.show', $document))
            ->assertOk()
            ->assertSee('Latest integrity check')
            ->assertSee('Verified')
            ->assertSee('Integrity verification history');
    }

    public function test_checksum_mismatch_is_recorded_without_changing_original_checksum(): void
    {
        Storage::fake('private');
        [$owner, $document] = $this->makeDocument();
        $originalContent = 'original policy file';
        $changedContent = 'changed policy file';
        $path = '1/policy.pdf';
        Storage::disk('private')->put($path, $changedContent);

        $originalChecksum = hash('sha256', $originalContent);
        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => $path,
            'original_filename' => 'policy.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($originalContent),
            'checksum' => $originalChecksum,
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
        ]);

        $this->actingAs($owner)
            ->post(route('documents.versions.verify-integrity', [$document, $version]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Integrity warning: the calculated SHA-256 does not match the stored checksum.');

        $this->assertDatabaseHas('document_version_integrity_checks', [
            'document_version_id' => $version->id,
            'stored_checksum' => $originalChecksum,
            'calculated_checksum' => hash('sha256', $changedContent),
            'status' => 'mismatch',
        ]);

        $this->assertSame($originalChecksum, $version->fresh()->checksum);
    }

    public function test_missing_private_file_is_recorded_as_missing(): void
    {
        Storage::fake('private');
        [$owner, $document] = $this->makeDocument();
        $content = 'original policy file';
        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => '1/missing.pdf',
            'original_filename' => 'missing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'uploaded_by' => $owner->id,
            'status' => DocumentVersionStatus::Published,
        ]);

        $this->actingAs($owner)
            ->post(route('documents.versions.verify-integrity', [$document, $version]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Integrity check failed: the file is missing from private storage.');

        $this->assertDatabaseHas('document_version_integrity_checks', [
            'document_version_id' => $version->id,
            'status' => 'missing',
            'calculated_checksum' => null,
        ]);
    }

    public function test_user_without_document_view_access_cannot_run_integrity_verification(): void
    {
        Storage::fake('private');
        [, $document] = $this->makeDocument();
        $other = User::query()->create([
            'name' => 'Other Employee',
            'email' => 'other-verify@example.test',
            'password' => 'testing-password',
            'department_id' => null,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);
        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => 1,
            'file_path' => '1/private.pdf',
            'original_filename' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
            'checksum' => hash('sha256', 'private'),
            'uploaded_by' => $document->owner_id,
            'status' => DocumentVersionStatus::Published,
        ]);

        $this->actingAs($other)
            ->post(route('documents.versions.verify-integrity', [$document, $version]))
            ->assertForbidden();

        $this->assertDatabaseCount('document_version_integrity_checks', 0);
    }

    private function makeDocument(): array
    {
        $department = Department::query()->create(['name' => 'Operations', 'code' => 'OPS']);
        $category = Category::query()->create(['name' => 'Governance']);
        $type = DocumentType::query()->create(['name' => 'Corporate Policy']);

        $owner = User::query()->create([
            'name' => 'Document Owner',
            'email' => 'owner@example.test',
            'password' => 'testing-password',
            'department_id' => $department->id,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $document = Document::query()->create([
            'reference_number' => 'REF-POLICY-001',
            'title' => 'Document reference policy',
            'description' => 'Metadata and version history test record.',
            'category_id' => $category->id,
            'document_type_id' => $type->id,
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'visibility' => DocumentVisibility::Confidential,
            'workflow_status' => DocumentWorkflowStatus::Published,
        ]);

        return [$owner, $document];
    }
}
