<?php

namespace App\Http\Middleware;

use App\Models\Assessment;
use App\Services\LessonPrerequisites;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLessonsCompleted
{
    public function __construct(private readonly LessonPrerequisites $prerequisites) {}

    public function handle(Request $request, Closure $next): Response
    {
        $assessment = $request->route('assessment');
        if ($request->routeIs('student.assessments.show') && $assessment instanceof Assessment
            && $assessment->attempts()->where('student_id', $request->user()->id)->exists()) {
            return $next($request);
        }

        $status = $this->prerequisites->status($request->user());
        if ($status['ready']) {
            return $next($request);
        }

        $message = $this->prerequisites->lockMessage($status);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('student.learning.index')->with('error', $message);
    }
}
