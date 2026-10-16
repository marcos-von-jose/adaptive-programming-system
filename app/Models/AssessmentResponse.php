<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentResponse extends Model
{
    protected $fillable = ['assessment_attempt_id', 'assessment_item_id', 'assessment_choice_id', 'is_correct'];

    protected function casts(): array { return ['is_correct' => 'boolean']; }
}
