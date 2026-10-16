@extends('layouts.dashboard')
@section('title', 'Lesson')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Lesson</p><h1>{{ $content->title }}</h1><p class="lead">{{ $content->summary }}</p></div>@if ($completed)<span class="status-pill">Completed</span>@endif</div>
@include('admin.partials.alerts')
<section class="panel">
    <div style="white-space:pre-wrap;line-height:1.8">{{ $content->body }}</div>
    @if ($content->video_path || $content->youtube_video_id)
        <div style="margin-top:24px">
            <h2 style="margin-bottom:14px">Lesson video</h2>
            @if ($content->video_path)
                <video controls preload="metadata" style="display:block;width:100%;max-width:960px;max-height:70vh;background:#000;border-radius:12px" src="{{ route('lessons.video', $content) }}">
                    Your browser does not support video playback.
                </video>
            @else
                <iframe src="{{ $content->youtubeEmbedUrl() }}" title="Video for {{ $content->title }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="display:block;width:100%;max-width:960px;aspect-ratio:16/9;border:0;border-radius:12px"></iframe>
                <p style="margin-top:12px"><a href="https://www.youtube.com/watch?v={{ $content->youtube_video_id }}" target="_blank" rel="noopener noreferrer">Open video on YouTube</a></p>
            @endif
        </div>
    @endif
    <div class="form-actions"><a class="button secondary" href="{{ route('student.learning.index') }}">All lessons</a>@if (! $completed)<form method="POST" action="{{ route('student.learning.complete', $content) }}">@csrf<button class="button">Mark complete</button></form>@endif</div>
</section>
@endsection
