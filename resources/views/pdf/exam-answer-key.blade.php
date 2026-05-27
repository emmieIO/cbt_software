<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Answer Key</title>
    <style>
        {!! file_get_contents(base_path('node_modules/katex/dist/katex.min.css')) ?: '' !!}
        @page { margin: 15mm 14mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; color: #101510; }
        .confidential { text-align: center; font-size: 8.5pt; color: #9f1239; font-weight: bold; border: 1px solid #fda4af; background: #fff1f2; padding: 4px 8px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1.4px; }
        .header { text-align: center; margin-bottom: 8px; }
        .logo { max-height: 42px; margin-bottom: 4px; }
        .school { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #084117; letter-spacing: 1px; }
        .title { font-size: 11.5pt; font-weight: bold; margin-top: 3px; }
        .meta { margin-top: 5px; font-size: 8.5pt; color: #485248; }
        .rule { border-top: 1.5px solid #084117; margin: 8px 0 10px; }

        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt; }
        .summary td { border: 1px solid #d7e2d8; background: #f7faf7; padding: 6px 8px; width: 25%; }
        .summary-label { display: block; font-size: 7pt; text-transform: uppercase; color: #647064; letter-spacing: 0.5px; margin-bottom: 2px; }
        .summary-value { font-weight: bold; color: #084117; font-size: 10pt; }

        .section-title { margin: 12px 0 5px; font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #084117; }
        .section-note { margin: 0 0 8px; font-size: 8.5pt; color: #555; }

        .detail-table { width: 100%; border-collapse: collapse; font-size: 8.8pt; }
        .detail-table th { background: #084117; color: #fff; padding: 6px 8px; text-align: left; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4px; }
        .detail-table td { padding: 6px 8px; border-bottom: 1px solid #dbe4dc; vertical-align: top; }
        .detail-table tr:nth-child(even) td { background: #f8fbf8; }
        .detail-no { width: 44px; font-weight: bold; color: #084117; }
        .detail-answer { width: 52px; text-align: center; }
        .answer-letter { display: inline-block; min-width: 18px; padding: 1px 6px; border-radius: 999px; background: #e7f3e8; color: #084117; font-size: 8.8pt; font-weight: bold; }
        .detail-note { color: #566056; font-size: 8.1pt; }
        .missing-text { color: #be123c; font-weight: bold; }
        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #d0d7d0; text-align: center; font-size: 7.5pt; color: #777; }
        .katex { font-size: 1em; }
        .katex-display { margin: 4px 0; text-align: center; }
        .pdf-math-fallback { font-family: 'DejaVu Sans Mono', monospace; }
    </style>
</head>
<body>

@php
    $lm = ['SS' => 'Senior Secondary', 'JS' => 'Junior Secondary', 'HP' => 'Higher Primary', 'LP' => 'Lower Primary'];
    $answers = $mcqs->values()->map(function ($q, $index) {
        $correct = $q->options->firstWhere('is_correct', true);
        $letter = null;

        if ($correct) {
            $position = $q->options->search(fn($o) => $o->id === $correct->id);
            $letter = $position !== false ? chr(65 + $position) : null;
        }

        return [
            'number' => $index + 1,
            'letter' => $letter,
            'content' => $correct?->content,
            'stem' => \Illuminate\Support\Str::limit($q->printableContent(), 80),
        ];
    });
@endphp

<div class="confidential">Confidential - For Examiners Only</div>

<div class="header">
    <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="logo" />
    <div class="school">Chrisland Schools</div>
    <div class="title">{{ $title }} - Answer Key</div>
    <div class="meta">{{ $subject }} | {{ $lm[$level] ?? $level }} Level | {{ $date }}</div>
</div>

<div class="rule"></div>

<table class="summary">
    <tr>
        <td>
            <span class="summary-label">MCQ Questions</span>
            <span class="summary-value">{{ $mcqs->count() }}</span>
        </td>
        <td>
            <span class="summary-label">Subject</span>
            <span class="summary-value">{{ $subject }}</span>
        </td>
        <td>
            <span class="summary-label">Level</span>
            <span class="summary-value">{{ $lm[$level] ?? $level }}</span>
        </td>
        <td>
            <span class="summary-label">Reference Date</span>
            <span class="summary-value">{{ $date }}</span>
        </td>
    </tr>
</table>

<div class="section-title">Detailed Answer Reference</div>
<p class="section-note">Official marking reference showing the correct letter, exact option wording, and a short question summary.</p>

@if($answers->isNotEmpty())
    <table class="detail-table">
        <tr>
            <th class="detail-no">No.</th>
            <th class="detail-answer">Ans.</th>
            <th>Correct Option</th>
            <th>Question Summary</th>
        </tr>
        @foreach($answers as $item)
            <tr>
                <td class="detail-no">{{ $item['number'] }}</td>
                <td class="detail-answer">
                    @if($item['letter'])
                        <span class="answer-letter">{{ $item['letter'] }}</span>
                    @else
                        <span class="missing-text">N/A</span>
                    @endif
                </td>
                <td>{!! \App\Support\RichContent::pdf($item['content'] ?? 'No answer set') !!}</td>
                <td class="detail-note">{{ $item['stem'] }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p style="color:#777;">No multiple choice questions in this exam.</p>
@endif

<div class="footer">Confidential • Chrisland Schools • Marking Reference Copy</div>
</body>
</html>
