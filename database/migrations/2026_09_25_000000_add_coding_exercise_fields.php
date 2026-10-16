<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->text('prompt')->nullable();
            $table->longText('starter_code')->nullable();
            $table->foreignId('programming_language_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
        });

        Schema::table('code_submissions', function (Blueprint $table) {
            $table->foreignId('exercise_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->longText('source_code')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at')->nullable();
        });

        Schema::table('student_feedback', function (Blueprint $table) {
            $table->foreignId('code_submission_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('code_submission_id');
        });
        Schema::table('code_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exercise_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn(['source_code', 'status', 'submitted_at']);
        });
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('programming_language_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['title', 'prompt', 'starter_code', 'status', 'published_at']);
        });
    }
};
