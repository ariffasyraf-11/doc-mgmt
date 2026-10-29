<?php

namespace Tests\Feature;

use App\Enums\DocumentVisibility;
use App\Enums\DocumentWorkflowStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAccess;
use App\Models\DocumentType;
use App\Models\User;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_an_organization_wide_document(): void
    {
        [$user, $document] = $this->makeDocument(DocumentVisibility::Organization);

        $this->assertTrue(app(DocumentPolicy::class)->view($user, $document));
    }

    public function test_confidential_document_requires_explicit_access_for_non_owner(): void
    {
        [$user, $document] = $this->makeDocument(DocumentVisibility::Confidential);

        $policy = app(DocumentPolicy::class);
        $this->assertFalse($policy->view($user, $document));

        DocumentAccess::query()->create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'access_level' => 'view',
        ]);

        $this->assertTrue($policy->view($user, $document));
    }

    private function makeDocument(DocumentVisibility $visibility): array
    {
        $department = Department::query()->create(['name' => 'Operations', 'code' => 'OPS']);
        $category = Category::query()->create(['name' => 'Policy']);
        $type = DocumentType::query()->create(['name' => 'Internal Policy']);

        $owner = User::query()->create([
            'name' => 'Document Owner',
            'email' => 'owner@example.test',
            'password' => 'password',
            'department_id' => $department->id,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $reader = User::query()->create([
            'name' => 'Reader',
            'email' => 'reader@example.test',
            'password' => 'password',
            'department_id' => $department->id,
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $document = Document::query()->create([
            'reference_number' => 'DOC-TEST-'.strtoupper(substr($visibility->value, 0, 4)),
            'title' => 'Access policy test',
            'category_id' => $category->id,
            'document_type_id' => $type->id,
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'visibility' => $visibility,
            'workflow_status' => DocumentWorkflowStatus::Draft,
        ]);

        return [$reader, $document];
    }
}
