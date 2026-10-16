<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeSubmission extends Model
{
    protected $fillable = ['exercise_id', 'student_id', 'source_code', 'source_language', 'status', 'submitted_at'];

    protected function casts(): array { return ['submitted_at' => 'datetime']; }
    public function exercise(): BelongsTo { return $this->belongsTo(Exercise::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function feedback(): HasMany { return $this->hasMany(StudentFeedback::class); }

    public function syncReviewStatus(): void
    {
        $latest = $this->feedback()->whereNotNull('review_outcome')->latest('created_at')->latest('id')->first();
        $hasFeedback = $this->feedback()->exists();
        $status = match ($latest?->review_outcome) {
            'correct' => 'correct',
            'incorrect' => 'needs_retry',
            default => $hasFeedback ? 'reviewed' : 'submitted',
        };
        $this->update(['status' => $status]);
    }
}
