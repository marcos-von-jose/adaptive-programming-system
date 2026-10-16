<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedInteger('time_limit_minutes')->nullable();
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->unsignedInteger('time_limit_minutes')->nullable();
        });

        Schema::create('student_activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'assessment_id']);
            $table->index(['student_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_activity_sessions');
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('time_limit_minutes');
        });
    }
};
