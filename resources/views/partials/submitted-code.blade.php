@php
    $codeLines = preg_split('/\r\n|\r|\n/', $submission->source_code ?? '');
    $flaggedLines = $submission->feedback->flatMap(fn ($entry) => $entry->markedErrorLines())->unique()->all();
    $selectedLines = $selectedLines ?? [];
@endphp
<div class="submitted-code" aria-label="Submitted code with line numbers">
    <ol>
        @foreach ($codeLines as $index => $line)
            <li id="code-line-{{ $submission->id }}-{{ $index + 1 }}" class="{{ in_array($index + 1, $flaggedLines) ? 'has-error' : '' }} {{ in_array($index + 1, $selectedLines) ? 'is-selected' : '' }}">
                <span class="code-line-number">{{ $index + 1 }}</span>
                <code>{{ $line === '' ? "\u{00A0}" : $line }}</code>
                @isset($linePicker)
                    <label class="code-line-pick"><input type="checkbox" name="error_lines[]" value="{{ $index + 1 }}" form="review-feedback-form" data-review-line aria-label="Mark error on line {{ $index + 1 }}" @checked(in_array($index + 1, $selectedLines))> Mark line</label>
                @endisset
            </li>
        @endforeach
    </ol>
</div>
