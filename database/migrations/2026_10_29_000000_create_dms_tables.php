<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 40)->default('employee')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('document_type_review_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('review_mode', 20)->default('sequential');
            $table->unsignedSmallInteger('required_reviewers')->default(1);
            $table->boolean('owner_can_review')->default(false);
            $table->string('rejection_mode', 30)->default('reject_cycle');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['document_type_id', 'is_active']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 100)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('visibility', 30)->default('department')->index();
            $table->string('workflow_status', 30)->default('draft')->index();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->date('next_review_date')->nullable()->index();
            $table->unsignedBigInteger('current_published_version_id')->nullable();
            $table->timestamps();
            $table->index(['department_id', 'workflow_status']);
            $table->index(['document_type_id', 'workflow_status']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->char('checksum', 64);
            $table->text('change_notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('supersedes_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->timestamps();
            $table->unique(['document_id', 'version_number']);
            $table->index(['document_id', 'status']);
        });

        Schema::create('document_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_version_id')->constrained('document_versions')->restrictOnDelete();
            $table->json('policy_snapshot');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'document_version_id']);
        });

        Schema::create('document_version_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_cycle_id')->constrained('document_review_cycles')->restrictOnDelete();
            $table->foreignId('document_version_id')->constrained('document_versions')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_number')->default(1);
            $table->string('status', 20)->default('pending')->index();
            $table->string('decision', 20)->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['review_cycle_id', 'reviewer_id']);
            $table->index(['reviewer_id', 'status']);
        });

        Schema::create('document_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('access_level', 20)->default('view');
            $table->timestamps();
            $table->unique(['document_id', 'user_id']);
        });

        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->morphs('subject');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('outcome', 30)->index();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['document_version_id', 'user_id']);
        });

        Schema::create('document_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('reminder_type', 40);
            $table->date('due_date');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'recipient_id', 'reminder_type', 'due_date'], 'document_reminder_dedupe');
            $table->index(['status', 'due_date']);
        });

        Schema::create('document_retention_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('retention_period_days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('document_retention_rules');
        Schema::dropIfExists('document_reminders');
        Schema::dropIfExists('document_downloads');
        Schema::dropIfExists('document_audit_logs');
        Schema::dropIfExists('document_access');
        Schema::dropIfExists('document_version_reviews');
        Schema::dropIfExists('document_review_cycles');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_type_review_policies');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');
    }
};
