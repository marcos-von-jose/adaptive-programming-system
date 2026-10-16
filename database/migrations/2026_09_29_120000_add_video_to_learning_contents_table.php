<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            $table->string('video_path')->nullable();
            $table->string('youtube_video_id', 11)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'youtube_video_id']);
        });
    }
};
