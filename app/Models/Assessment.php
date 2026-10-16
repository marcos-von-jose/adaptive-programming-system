<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = ['title', 'description', 'competency_id', 'created_by', 'status', 'passing_score', 'time_limit_minutes'];

    public function competency(): BelongsTo { return $this->belongsTo(Competency::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(AssessmentItem::class)->orderBy('position'); }
    public function attempts(): HasMany { return $this->hasMany(AssessmentAttempt::class); }
    public function timedSessions(): HasMany { return $this->hasMany(StudentActivitySession::class); }
}
