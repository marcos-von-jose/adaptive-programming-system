<div class="page-heading"><div><p class="eyebrow">Assessment</p><h1>{{ $assessment->title }}</h1><p class="lead">{{ $assessment->description }}</p></div></div>
@if ($requiresTimer)<div class="activity-timer" role="timer">Time remaining: <strong data-time-remaining>--:--</strong></div>@endif
<form method="POST" action="{{ route('student.assessments.store', $assessment) }}" data-student-activity-locked>@csrf
    <p class="form-help" style="margin:0 0 18px">Copy, paste, and Find keyboard shortcuts are disabled while you take this assessment.</p>
    @foreach ($assessment->items as $item)
        <section class="panel" style="margin-bottom:18px"><div class="panel-header"><h2>{{ $loop->iteration }}. {{ $item->prompt }}</h2></div><div class="list">
            @foreach ($item->choices as $choice)
                <label class="list-item"><input type="radio" name="answers[{{ $item->id }}]" value="{{ $choice->id }}" @checked(old('answers.'.$item->id) == $choice->id) required><span class="list-copy">{{ $choice->text }}</span></label>
            @endforeach
        </div></section>
    @endforeach
    <div class="form-actions"><a class="button secondary" href="{{ route('student.assessments.index') }}">Back</a><button class="button">Submit assessment</button></div>
</form>
