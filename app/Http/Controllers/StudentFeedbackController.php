<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $feedback = $request->user()->feedback()->with(['author', 'attempt.assessment', 'codeSubmission.exercise'])->latest()->paginate(15);
        return view('student.feedback.index', compact('feedback'));
    }
}
