<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $fillable = ['assessment_id', 'student_id', 'score', 'completed_at'];

    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function responses(): HasMany { return $this->hasMany(AssessmentResponse::class); }
}
