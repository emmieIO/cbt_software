<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Answer Key</title>
    <style>
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #111;
        }
        .sheet {
            min-height: 260mm;
            padding: 7mm 8mm;
            border: 1px solid #777;
            page-break-inside: avoid;
        }
        .confidential {
            margin-bottom: 3mm;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .brand {
            margin-bottom: 5mm;
            text-align: center;
            white-space: nowrap;
        }
        .brand-logo {
            height: 39px;
            margin-right: 7px;
            vertical-align: middle;
        }
        .brand-name {
            font-size: 25pt;
            font-weight: bold;
            vertical-align: middle;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 4mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .meta-table td {
            height: 7mm;
            padding: 1mm 2mm;
            border: 1px solid #888;
            font-size: 8.5pt;
        }
        .meta-label {
            width: 18mm;
            font-weight: bold;
            white-space: nowrap;
        }
        .meta-value {
            font-weight: bold;
            text-transform: uppercase;
        }
        .key-title {
            margin: 1mm 0 1mm;
            text-align: center;
            font-family: 'DejaVu Serif', serif;
            font-size: 17pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .key-note {
            margin-bottom: 4mm;
            text-align: center;
            font-size: 7.5pt;
        }
        .key-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .key-grid > tbody > tr > td {
            width: 25%;
            padding: 0 2mm;
            vertical-align: top;
        }
        .key-grid > tbody > tr > td:first-child {
            padding-left: 0;
        }
        .key-grid > tbody > tr > td:last-child {
            padding-right: 0;
        }
        .key-row {
            width: 100%;
            height: 6mm;
            border-collapse: collapse;
        }
        .key-row td {
            padding: 0;
            border: none;
            vertical-align: middle;
        }
        .question-number {
            width: 9mm;
            padding-right: 1mm !important;
            font-size: 8pt;
            text-align: right;
        }
        .bubble-cell {
            text-align: center;
        }
        .bubble {
            display: inline-block;
            width: 4.4mm;
            height: 4.4mm;
            border: 1px solid #555;
            border-radius: 50%;
            font-size: 6.7pt;
            line-height: 4.1mm;
            text-align: center;
        }
        .bubble.correct {
            border-color: #111;
            background: #111;
            color: #fff;
            font-weight: bold;
        }
        .missing {
            color: #9f1239;
            font-size: 7pt;
            font-weight: bold;
        }
        .legend {
            width: 62%;
            margin: 5mm auto 0;
            border-collapse: collapse;
        }
        .legend td {
            padding: 1.5mm 2mm;
            border: 1px solid #999;
            font-size: 7.5pt;
            text-align: center;
        }
        .legend-bubble {
            display: inline-block;
            width: 4.4mm;
            height: 4.4mm;
            margin-right: 1mm;
            border-radius: 50%;
            background: #111;
            color: #fff;
            font-size: 6.7pt;
            line-height: 4.1mm;
            text-align: center;
            vertical-align: middle;
        }
        .approval-table {
            width: 100%;
            margin-top: 7mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .approval-table td {
            width: 50%;
            padding: 5mm 3mm 1mm;
            border: 1px solid #888;
            font-size: 8pt;
            font-weight: bold;
        }
        .empty-state {
            padding: 20mm 0;
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>
@php
    $logoPath = public_path('assets/img/chrisland-school-logo.png');
    $logoSource = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
        : null;

    $answers = $mcqs->values()->map(function ($question, $index) {
        $correctIndex = $question->options->values()->search(
            fn ($option) => (bool) $option->is_correct,
        );

        return [
            'number' => $index + 1,
            'letter' => $correctIndex === false ? null : chr(65 + $correctIndex),
        ];
    });

    $keyColumnCount = min(4, max(1, $answers->count()));
    $keyRowsPerColumn = (int) ceil($answers->count() / $keyColumnCount);
    $keyColumns = collect(range(0, $keyColumnCount - 1))
        ->map(fn (int $column) => $answers->slice($column * $keyRowsPerColumn, $keyRowsPerColumn)->values());
    $keyOptionCount = max(4, (int) ($mcqs->max(fn ($question) => $question->options->count()) ?? 4));
    $keyLetters = range('A', chr(64 + min($keyOptionCount, 5)));
@endphp

<div class="sheet">
    <div class="confidential">Confidential - For Examiners Only</div>

    <div class="brand">
        @if($logoSource)
            <img src="{{ $logoSource }}" alt="Chrisland Schools" class="brand-logo" />
        @endif
        <span class="brand-name">CHRISLAND SCHOOLS</span>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">EXAM:</td>
            <td colspan="3" class="meta-value">{{ $title }}</td>
        </tr>
        <tr>
            <td class="meta-label">SUBJECT:</td>
            <td class="meta-value">{{ $subject }}</td>
            <td class="meta-label">CLASS:</td>
            <td class="meta-value">{{ $classLevel ?: $level }}</td>
        </tr>
        <tr>
            <td class="meta-label">SESSION:</td>
            <td class="meta-value">{{ $academicSession }}</td>
            <td class="meta-label">QUESTIONS:</td>
            <td class="meta-value">{{ $answers->count() }}</td>
        </tr>
    </table>

    <div class="key-title">Multiple Choice Answer Key</div>
    <div class="key-note">The filled circle indicates the official correct answer for each question.</div>

    @if($answers->isNotEmpty())
        <table class="key-grid">
            <tr>
                @foreach($keyColumns as $columnAnswers)
                    <td>
                        @foreach($columnAnswers as $answer)
                            <table class="key-row">
                                <tr>
                                    <td class="question-number">{{ $answer['number'] }}.</td>
                                    @foreach($keyLetters as $letter)
                                        <td class="bubble-cell">
                                            <span class="bubble {{ $answer['letter'] === $letter ? 'correct' : '' }}">{{ $letter }}</span>
                                        </td>
                                    @endforeach
                                    @if($answer['letter'] === null)
                                        <td class="missing">N/A</td>
                                    @endif
                                </tr>
                            </table>
                        @endforeach
                    </td>
                @endforeach
                @for($column = $keyColumns->count(); $column < 4; $column++)
                    <td></td>
                @endfor
            </tr>
        </table>
    @else
        <div class="empty-state">No multiple choice questions in this exam.</div>
    @endif

    <table class="legend">
        <tr>
            <td><span class="legend-bubble">A</span> Filled circle = correct option</td>
            <td>N/A = no correct option configured</td>
        </tr>
    </table>

    <table class="approval-table">
        <tr>
            <td>Prepared by / Signature:</td>
            <td>Verified by / Signature:</td>
        </tr>
    </table>
</div>
</body>
</html>
