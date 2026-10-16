<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentChoice extends Model
{
    protected $fillable = ['assessment_item_id', 'text', 'is_correct'];

    protected function casts(): array { return ['is_correct' => 'boolean']; }
    public function item(): BelongsTo { return $this->belongsTo(AssessmentItem::class, 'assessment_item_id'); }
}
