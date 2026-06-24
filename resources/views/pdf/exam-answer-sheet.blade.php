<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Multiple Choice Answer Sheet</title>
    <style>
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #111;
        }
        .sheet {
            height: 260mm;
            padding: 8mm 8mm 7mm;
            border: 1px solid #777;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .brand {
            margin-bottom: 6mm;
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
        .candidate-layout {
            width: 100%;
            margin-bottom: 4mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .candidate-layout td {
            padding: 0;
            border: none;
            vertical-align: top;
        }
        .candidate-layout .details-cell {
            width: 78%;
            padding-right: 2mm;
        }
        .candidate-layout .score-cell {
            width: 22%;
        }
        .details-table,
        .score-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .details-table td,
        .score-table td {
            height: 6.5mm;
            padding: 1mm 2mm;
            border: 1px solid #888;
            font-size: 8pt;
        }
        .details-label {
            font-weight: bold;
            white-space: nowrap;
        }
        .details-value {
            font-weight: bold;
            text-transform: uppercase;
        }
        .session-value {
            padding-left: 3mm !important;
        }
        .score-table th {
            height: 6.5mm;
            border: 1px solid #888;
            font-size: 9pt;
            text-align: center;
        }
        .score-box {
            height: 18.5mm !important;
        }
        .sheet-title {
            margin: 1mm 0 0;
            text-align: center;
            font-family: 'DejaVu Serif', serif;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .instructions {
            width: 70%;
            margin: 0 auto 3mm;
            padding: 1.5mm 2mm;
            border: 1px solid #aaa;
            font-size: 6.5pt;
            line-height: 1.35;
        }
        .bubble-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .bubble-grid > tbody > tr > td {
            width: 25%;
            padding: 0 2mm;
            vertical-align: top;
        }
        .bubble-grid > tbody > tr > td:first-child {
            padding-left: 0;
        }
        .bubble-grid > tbody > tr > td:last-child {
            padding-right: 0;
        }
        .answer-row {
            width: 100%;
            height: 5.8mm;
            border-collapse: collapse;
        }
        .answer-row td {
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
            width: 4.2mm;
            height: 4.2mm;
            border: 1px solid #555;
            border-radius: 50%;
            font-size: 6.7pt;
            line-height: 3.9mm;
            text-align: center;
        }
        .teacher-use {
            margin-top: 3mm;
        }
        .teacher-use-title {
            margin-bottom: 1mm;
            font-size: 7pt;
            font-weight: bold;
        }
        .teacher-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .teacher-table th,
        .teacher-table td {
            height: 7mm;
            padding: 0.5mm;
            border: 1px solid #777;
            font-size: 6.5pt;
            text-align: center;
        }
        .teacher-table th {
            width: 19mm;
            text-align: left;
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
    $answerQuestions = $mcqs->values();
    $answerColumnCount = min(4, max(1, $answerQuestions->count()));
    $answerRowsPerColumn = (int) ceil($answerQuestions->count() / $answerColumnCount);
    $answerColumns = collect(range(0, $answerColumnCount - 1))
        ->map(fn (int $column) => $answerQuestions->slice($column * $answerRowsPerColumn, $answerRowsPerColumn)->values());
    $answerOptionCount = max(4, (int) ($answerQuestions->max(fn ($question) => $question->options->count()) ?? 4));
    $answerLetters = range('A', chr(64 + min($answerOptionCount, 5)));
    $teacherQuestionCount = min(15, max(1, $answerQuestions->count()));
@endphp

<div class="sheet">
    <div class="brand">
        @if($logoSource)
            <img src="{{ $logoSource }}" alt="Chrisland Schools" class="brand-logo" />
        @endif
        <span class="brand-name">CHRISLAND SCHOOLS</span>
    </div>

    <table class="candidate-layout">
        <tr>
            <td class="details-cell">
                <table class="details-table">
                    <colgroup>
                        <col style="width:16%;">
                        <col style="width:16%;">
                        <col style="width:25%;">
                        <col style="width:13%;">
                        <col style="width:17%;">
                        <col style="width:13%;">
                    </colgroup>
                    <tr>
                        <td class="details-label">NAME:</td>
                        <td colspan="5"></td>
                    </tr>
                    <tr>
                        <td class="details-label">CLASS:</td>
                        <td class="details-value">{{ strtoupper($classLevel ?? '') }}</td>
                        <td class="details-label">TERM/SESSION:</td>
                        <td colspan="3" class="details-value session-value">{{ $academicSession }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">SUBJECT:</td>
                        <td colspan="2" class="details-value">{{ strtoupper($subject) }}</td>
                        <td class="details-label">DATE:</td>
                        <td></td>
                        <td class="details-label">UNIT:</td>
                    </tr>
                </table>
            </td>
            <td class="score-cell">
                <table class="score-table">
                    <tr><th>TOTAL SCORE</th></tr>
                    <tr><td class="score-box"></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="sheet-title">Multiple Choice Answer Sheet</div>
    <div class="instructions">
        Use HB pencil throughout.<br>
        Answer each question by choosing one letter and shading it completely. Erase completely any answer you wish to change.
    </div>

    @if($answerQuestions->isNotEmpty())
        <table class="bubble-grid">
            <tr>
                @foreach($answerColumns as $column => $columnQuestions)
                    <td>
                        @foreach($columnQuestions as $index => $question)
                            @php $questionNumber = ($column * $answerRowsPerColumn) + $index + 1; @endphp
                            <table class="answer-row">
                                <tr>
                                    <td class="question-number">{{ $questionNumber }}.</td>
                                    @foreach($answerLetters as $letter)
                                        <td class="bubble-cell"><span class="bubble">{{ $letter }}</span></td>
                                    @endforeach
                                </tr>
                            </table>
                        @endforeach
                    </td>
                @endforeach
                @for($column = $answerColumns->count(); $column < 4; $column++)
                    <td></td>
                @endfor
            </tr>
        </table>
    @else
        <div class="empty-state">No multiple choice questions in this exam.</div>
    @endif

    <div class="teacher-use">
        <div class="teacher-use-title">FOR TEACHER'S USE</div>
        <table class="teacher-table">
            <tr>
                <th>Question Number</th>
                @for($number = 1; $number <= $teacherQuestionCount; $number++)
                    <td>{{ $number }}</td>
                @endfor
            </tr>
            <tr>
                <th>Marks Obtained</th>
                @for($number = 1; $number <= $teacherQuestionCount; $number++)
                    <td></td>
                @endfor
            </tr>
        </table>
    </div>
</div>
</body>
</html>
