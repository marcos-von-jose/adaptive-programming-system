<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('competency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('passing_score')->default(70);
        });

        Schema::table('assessment_items', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->unsignedInteger('position')->default(0);
        });

        Schema::table('assessment_choices', function (Blueprint $table) {
            $table->foreignId('assessment_item_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->boolean('is_correct')->default(false);
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamp('completed_at');
        });

        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_choice_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_correct');
            $table->unique(['assessment_attempt_id', 'assessment_item_id'], 'attempt_item_unique');
        });

        Schema::table('learning_progress', function (Blueprint $table) {
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('learning_content_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            $table->unique(['student_id', 'learning_content_id']);
        });

        Schema::create('student_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assessment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_feedback');
        Schema::table('learning_progress', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'learning_content_id']);
            $table->dropConstrainedForeignId('student_id');
            $table->dropConstrainedForeignId('learning_content_id');
            $table->dropColumn(['status', 'completed_at']);
        });
        Schema::table('assessment_responses', function (Blueprint $table) {
            $table->dropUnique('attempt_item_unique');
            $table->dropConstrainedForeignId('assessment_attempt_id');
            $table->dropConstrainedForeignId('assessment_item_id');
            $table->dropConstrainedForeignId('assessment_choice_id');
            $table->dropColumn('is_correct');
        });
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn(['score', 'completed_at']);
        });
        Schema::table('assessment_choices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_item_id');
            $table->dropColumn(['text', 'is_correct']);
        });
        Schema::table('assessment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_id');
            $table->dropColumn(['prompt', 'position']);
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competency_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['title', 'description', 'status', 'passing_score']);
        });
    }
};
