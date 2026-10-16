<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_contents', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->string('slug')->unique()->after('title');
            $table->text('summary')->nullable()->after('slug');
            $table->longText('body')->after('summary');
            $table->foreignId('competency_id')->nullable()->after('body')->constrained()->nullOnDelete();
            $table->foreignId('programming_language_id')->nullable()->after('competency_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('programming_language_id')->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->after('created_by');
            $table->timestamp('published_at')->nullable()->after('status');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('system_settings');

        Schema::table('learning_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competency_id');
            $table->dropConstrainedForeignId('programming_language_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['title', 'slug', 'summary', 'body', 'status', 'published_at']);
        });
    }
};
