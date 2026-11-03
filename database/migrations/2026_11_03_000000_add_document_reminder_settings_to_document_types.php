<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->json('expiry_reminder_days')->nullable();
            $table->json('expiry_reminder_recipients')->nullable();
            $table->json('next_review_reminder_days')->nullable();
            $table->json('next_review_reminder_recipients')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn([
                'expiry_reminder_days',
                'expiry_reminder_recipients',
                'next_review_reminder_days',
                'next_review_reminder_recipients',
            ]);
        });
    }
};
