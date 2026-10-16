<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningContent extends Model
{
    protected $fillable = [
        'title', 'slug', 'summary', 'body', 'competency_id',
        'programming_language_id', 'created_by', 'status', 'published_at',
        'video_path', 'youtube_video_id',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    public function programmingLanguage(): BelongsTo
    {
        return $this->belongsTo(ProgrammingLanguage::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(LearningProgress::class, 'learning_content_id');
    }

    public function youtubeEmbedUrl(): ?string
    {
        return $this->youtube_video_id
            ? 'https://www.youtube-nocookie.com/embed/'.$this->youtube_video_id
            : null;
    }
}
