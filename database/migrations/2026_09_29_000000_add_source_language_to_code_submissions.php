<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('code_submissions', function (Blueprint $table) {
            $table->string('source_language', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('code_submissions', function (Blueprint $table) {
            $table->dropColumn('source_language');
        });
    }
};
