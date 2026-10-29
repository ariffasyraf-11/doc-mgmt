<?php

namespace Database\Seeders;

use App\Enums\ReviewMode;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\DocumentTypeReviewPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $department = Department::query()->firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Operations', 'is_active' => true],
        );

        Category::query()->firstOrCreate(
            ['name' => 'General'],
            ['description' => 'General internal documents', 'is_active' => true],
        );

        $type = DocumentType::query()->firstOrCreate(
            ['name' => 'Internal Document'],
            ['description' => 'Default internal document type', 'is_active' => true],
        );

        DocumentTypeReviewPolicy::query()->firstOrCreate(
            ['document_type_id' => $type->id, 'is_active' => true],
            [
                'review_mode' => ReviewMode::Sequential,
                'required_reviewers' => 1,
                'owner_can_review' => false,
                'rejection_mode' => 'reject_cycle',
            ],
        );

        $email = trim((string) env('DMS_ADMIN_EMAIL'));
        $password = (string) env('DMS_ADMIN_PASSWORD');

        if ($email !== '' && $password !== '') {
            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => env('DMS_ADMIN_NAME', 'System Administrator'),
                    'password' => $password,
                    'department_id' => $department->id,
                    'role' => UserRole::Administrator,
                    'is_active' => true,
                ],
            );
        }
    }
}
