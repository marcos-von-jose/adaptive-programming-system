<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentActivitySession extends Model
{
    protected $fillable = ['student_id', 'assessment_id', 'exercise_id', 'started_at', 'expires_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function exercise(): BelongsTo { return $this->belongsTo(Exercise::class); }
}
