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
