<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('programming_languages', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->string('slug')->unique()->after('name');
            $table->string('version')->nullable()->after('slug');
            $table->string('status')->default('active')->after('version');
        });

        Schema::table('competency_levels', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->unsignedTinyInteger('min_score')->after('name');
            $table->unsignedTinyInteger('max_score')->after('min_score');
            $table->unsignedTinyInteger('rank')->unique()->after('max_score');
            $table->text('description')->nullable()->after('rank');
        });

        Schema::table('competencies', function (Blueprint $table) {
            $table->string('code')->unique()->after('id');
            $table->string('name')->after('code');
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('sequence')->after('description');
            $table->string('status')->default('active')->after('sequence');
        });
    }

    public function down(): void
    {
        Schema::table('competencies', function (Blueprint $table) {
            $table->dropColumn(['code', 'name', 'description', 'sequence', 'status']);
        });

        Schema::table('competency_levels', function (Blueprint $table) {
            $table->dropColumn(['name', 'min_score', 'max_score', 'rank', 'description']);
        });

        Schema::table('programming_languages', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug', 'version', 'status']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });
    }
};
