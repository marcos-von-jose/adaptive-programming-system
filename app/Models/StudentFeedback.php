<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeedback extends Model
{
    protected $table = 'student_feedback';
    protected $fillable = ['student_id', 'author_id', 'assessment_attempt_id', 'code_submission_id', 'message', 'review_outcome', 'error_line', 'error_lines', 'error_message'];

    protected function casts(): array { return ['error_lines' => 'array']; }

    public function markedErrorLines(): array
    {
        return $this->error_lines ?: ($this->error_line ? [(int) $this->error_line] : []);
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function attempt(): BelongsTo { return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id'); }
    public function codeSubmission(): BelongsTo { return $this->belongsTo(CodeSubmission::class); }
}
