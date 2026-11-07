<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_version_integrity_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained('document_versions')->restrictOnDelete();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->char('stored_checksum', 64)->nullable();
            $table->char('calculated_checksum', 64)->nullable();
            $table->string('status', 30)->index();
            $table->timestamp('checked_at')->useCurrent()->index();
            $table->index(['document_version_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_version_integrity_checks');
    }
};
