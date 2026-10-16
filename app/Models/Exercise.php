<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = ['title', 'prompt', 'starter_code', 'programming_language_id', 'created_by', 'status', 'published_at', 'time_limit_minutes'];

    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function programmingLanguage(): BelongsTo { return $this->belongsTo(ProgrammingLanguage::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions(): HasMany { return $this->hasMany(CodeSubmission::class); }
    public function timedSessions(): HasMany { return $this->hasMany(StudentActivitySession::class); }
}
