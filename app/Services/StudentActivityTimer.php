<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Exercise;
use App\Models\StudentActivitySession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentActivityTimer
{
    public function latest(User $student, Assessment|Exercise $activity): ?StudentActivitySession
    {
        $column = $activity instanceof Assessment ? 'assessment_id' : 'exercise_id';

        return StudentActivitySession::query()
            ->where('student_id', $student->id)
            ->where($column, $activity->id)
            ->latest('id')
            ->first();
    }

    public function start(User $student, Assessment|Exercise $activity): StudentActivitySession
    {
        return DB::transaction(function () use ($student, $activity): StudentActivitySession {
            // Serialize concurrent starts by the same student, including separate browser tabs.
            User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $latest = $this->latest($student, $activity);
            if ($latest && ($activity instanceof Assessment || $latest->completed_at === null)) {
                return $latest;
            }

            $now = now();
            return StudentActivitySession::create([
                'student_id' => $student->id,
                'assessment_id' => $activity instanceof Assessment ? $activity->id : null,
                'exercise_id' => $activity instanceof Exercise ? $activity->id : null,
                'started_at' => $now,
                'expires_at' => $now->copy()->addMinutes($activity->time_limit_minutes),
            ]);
        });
    }

    public function isActive(?StudentActivitySession $session): bool
    {
        return $session && $session->completed_at === null && $session->expires_at->isFuture();
    }

    public function remainingMilliseconds(StudentActivitySession $session): int
    {
        return max(0, $session->expires_at->getTimestampMs() - now()->getTimestampMs());
    }
}
