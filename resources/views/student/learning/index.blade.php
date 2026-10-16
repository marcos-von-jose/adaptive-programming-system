@extends('layouts.dashboard')
@section('title', 'My Learning')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">Learning library</p>
        <h1>My learning</h1>
        <p class="lead">Explore published lessons and track what you have completed.</p>
    </div>
</div>
@include('admin.partials.alerts')
@if ($contents->isNotEmpty())
    <div class="notice">
        @if ($remainingCount === 0)
            You have completed all published lessons. Assessments and coding exercises are now available.
        @else
            Complete all published lessons to unlock assessments and coding exercises. {{ $remainingCount }} {{ $remainingCount === 1 ? 'lesson remains' : 'lessons remain' }}.
        @endif
    </div>
@endif
<section class="panel">
    <div class="list">
        @forelse ($contents as $content)
            <div class="list-item">
                <span class="list-icon">{{ in_array($content->id, $completedIds) ? '✓' : '→' }}</span>
                <div class="list-copy">
                    <div class="list-title">{{ $content->title }}</div>
                    <div class="list-subtitle">
                        {{ $content->competency?->name ?? 'General' }} · {{ Str::limit($content->summary, 90) }}
                        @if ($content->video_path || $content->youtube_video_id)
                            · Video lesson
                        @endif
                    </div>
                </div>
                <a class="button secondary small" href="{{ route('student.learning.show', $content) }}">Open lesson</a>
            </div>
        @empty
            <div class="empty">No lessons are published yet.</div>
        @endforelse
    </div>
</section>
@endsection
