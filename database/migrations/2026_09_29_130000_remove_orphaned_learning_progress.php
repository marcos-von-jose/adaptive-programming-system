<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('learning_progress')
            ->whereNotIn('learning_content_id', DB::table('learning_contents')->select('id'))
            ->delete();
    }

    public function down(): void
    {
        // Deleted lessons and their progress cannot be restored by a rollback.
    }
};
