<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentItem extends Model
{
    protected $fillable = ['assessment_id', 'prompt', 'position'];

    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function choices(): HasMany { return $this->hasMany(AssessmentChoice::class); }
}
