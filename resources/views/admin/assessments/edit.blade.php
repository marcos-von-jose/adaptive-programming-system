@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Edit Assessment')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Learning evaluation</p><h1>{{ $assessment->title }}</h1><p class="lead">Manage the assessment details and answer choices.</p></div></div>
@include('admin.partials.alerts')
<section class="panel" style="margin-bottom:18px"><div class="panel-header"><h2>Assessment details</h2><span class="badge {{ $assessment->status === 'published' ? 'success' : 'warning' }}">{{ $assessment->status }}</span></div><form method="POST" action="{{ route($managementPrefix.'.assessments.update', $assessment) }}">@csrf @method('PUT') @include('admin.assessments._form')</form></section>
<section class="panel" style="margin-bottom:18px"><div class="panel-header"><h2>Questions</h2><span class="panel-kicker">{{ $assessment->items->count() }} total</span></div>
    @forelse ($assessment->items as $item)
    @if ($assessment->attempts_count === 0)
    <details style="padding:12px 0;border-top:1px solid var(--line)"><summary style="cursor:pointer;font-size:12px;font-weight:700">Edit question {{ $loop->iteration }}</summary>
        <form method="POST" action="{{ route($managementPrefix.'.assessments.questions.update', [$assessment, $item]) }}" style="padding:16px 0">@csrf @method('PUT')
            <div class="form-grid"><div class="form-group full"><label class="form-label" for="prompt-{{ $item->id }}">Question</label><textarea class="textarea" id="prompt-{{ $item->id }}" name="prompt" required>{{ $item->prompt }}</textarea></div>
                @foreach ($item->choices as $choice)<div class="form-group"><label class="form-label" for="choice-{{ $choice->id }}">Choice {{ $loop->iteration }}</label><input class="field" id="choice-{{ $choice->id }}" name="choices[]" value="{{ $choice->text }}" required></div>@endforeach
                <div class="form-group"><label class="form-label" for="correct-{{ $item->id }}">Correct choice</label><select class="select" id="correct-{{ $item->id }}" name="correct_choice">@foreach ($item->choices as $choice)<option value="{{ $loop->index }}" @selected($choice->is_correct)>Choice {{ $loop->iteration }}</option>@endforeach</select></div>
            </div><div class="form-actions"><button class="button small">Save question</button></div>
        </form>
    </details>
    @endif
    <div class="list-item"><div class="list-copy"><div class="list-title">{{ $loop->iteration }}. {{ $item->prompt }}</div><div class="list-subtitle">@foreach ($item->choices as $choice){{ $choice->text }}{{ $choice->is_correct ? ' (correct)' : '' }}{{ ! $loop->last ? ' · ' : '' }}@endforeach</div></div>@if ($assessment->attempts_count === 0)<form method="POST" action="{{ route($managementPrefix.'.assessments.questions.destroy', [$assessment, $item]) }}" onsubmit="return confirm('Remove this question?')">@csrf @method('DELETE')<button class="button danger small">Remove</button></form>@endif</div>
    @empty <div class="empty">Add the first question below.</div> @endforelse
</section>
@if ($assessment->attempts_count === 0)
<section class="panel"><div class="panel-header"><h2>Add a question</h2></div><form method="POST" action="{{ route($managementPrefix.'.assessments.questions.store', $assessment) }}">@csrf<div class="form-grid"><div class="form-group full"><label class="form-label" for="prompt">Question</label><textarea class="textarea" id="prompt" name="prompt" required>{{ old('prompt') }}</textarea></div>@for ($i = 0; $i < 4; $i++)<div class="form-group"><label class="form-label" for="choice-{{ $i }}">Choice {{ $i + 1 }}</label><input class="field" id="choice-{{ $i }}" name="choices[]" value="{{ old('choices.'.$i) }}" required></div>@endfor<div class="form-group"><label class="form-label" for="correct_choice">Correct choice</label><select class="select" id="correct_choice" name="correct_choice">@for ($i = 0; $i < 4; $i++)<option value="{{ $i }}" @selected(old('correct_choice', 0) == $i)>Choice {{ $i + 1 }}</option>@endfor</select></div></div><div class="form-actions"><button class="button">Add question</button></div></form></section>
@else <div class="notice">Questions are locked because students have submitted this assessment.</div> @endif
@if ($expiredSessions->isNotEmpty())
<section class="panel" style="margin-top:18px"><div class="panel-header"><h2>Expired student timers</h2></div><div class="list">
    @foreach ($expiredSessions as $session)
    <div class="list-item"><div class="list-copy"><div class="list-title">{{ $session->student->name }}</div><div class="list-subtitle">Expired {{ $session->expires_at->format('M j, Y g:i A') }}</div></div>
        <form method="POST" action="{{ route($managementPrefix.'.assessments.timers.reset', [$assessment, $session->student]) }}">@csrf<button class="button secondary small" type="submit">Reset timer</button></form>
    </div>
    @endforeach
</div></section>
@endif
@endsection
