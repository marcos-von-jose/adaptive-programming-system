<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_feedback', function (Blueprint $table) {
            $table->string('review_outcome', 20)->nullable();
            $table->unsignedInteger('error_line')->nullable();
            $table->text('error_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_feedback', function (Blueprint $table) {
            $table->dropColumn(['review_outcome', 'error_line', 'error_message']);
        });
    }
};
