<?php

namespace App\Services;

use App\Models\LearningContent;
use App\Models\User;

class LessonPrerequisites
{
    public function status(User $student): array
    {
        $published = LearningContent::query()->where('status', 'published');
        $total = (clone $published)->count();
        $remaining = (clone $published)->whereDoesntHave('learningProgress', function ($query) use ($student) {
            $query->where('student_id', $student->id)->where('status', 'completed');
        })->count();

        return [
            'total' => $total,
            'remaining' => $remaining,
            'ready' => $total > 0 && $remaining === 0,
        ];
    }

    public function lockMessage(array $status): string
    {
        if ($status['total'] === 0) {
            return 'Assessments and coding exercises are locked until an instructor or administrator publishes a lesson and you complete it.';
        }

        $count = $status['remaining'];

        return 'Complete all published learning materials before starting an assessment or coding exercise. '
            .$count.' '.($count === 1 ? 'lesson remains.' : 'lessons remain.');
    }
}
