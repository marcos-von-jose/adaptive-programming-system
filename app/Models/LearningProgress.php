<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningProgress extends Model
{
    protected $table = 'learning_progress';
    protected $fillable = ['student_id', 'learning_content_id', 'status', 'completed_at'];

    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function content(): BelongsTo { return $this->belongsTo(LearningContent::class, 'learning_content_id'); }
}
