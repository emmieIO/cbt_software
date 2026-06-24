<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        {!! file_get_contents(base_path('node_modules/katex/dist/katex.min.css')) ?: '' !!}
        @page { margin: 15mm 14mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.45; color: #111; }

        .paper-header { text-align: center; margin-bottom: 5px; }
        .paper-brand { white-space: nowrap; }
        .paper-logo { height: 31px; margin-right: 6px; vertical-align: middle; }
        .paper-school { font-size: 19pt; font-weight: bold; vertical-align: middle; }
        .paper-title { margin-top: 2px; font-size: 10.5pt; font-weight: bold; text-transform: uppercase; }
        .paper-session { margin-top: 1px; font-size: 10pt; font-weight: bold; }
        .paper-subject { margin-top: 1px; font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .paper-meta { width: 100%; margin-top: 2px; border-collapse: collapse; font-size: 9.5pt; font-weight: bold; }
        .paper-meta td { padding: 0; border: none; }
        .paper-meta td:first-child { text-align: left; }
        .paper-meta td:last-child { text-align: right; }

        .instructions { margin: 3px auto 6px; max-width: 94%; text-align: center; font-size: 8.5pt; line-height: 1.3; }
        .instructions strong { color: #111; }

        .section-title { margin: 6px 0 2px; text-align: center; font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #111; }
        .section-note { margin: 0 0 6px; text-align: center; font-size: 8.5pt; font-weight: bold; color: #222; }
        .exam-section.page-break { page-break-before: always; }

        .question-list { margin: 0; padding: 0; }
        .question-item { margin: 0 0 7px; page-break-inside: avoid; }
        .question-row { display: table; width: 100%; }
        .question-no { display: table-cell; width: 19px; font-weight: normal; vertical-align: top; }
        .question-body { display: table-cell; vertical-align: top; }
        .marks { font-size: 8pt; color: #555; font-weight: bold; white-space: nowrap; }
        .question-image { margin: 4px 0 4px 0; }
        .question-image img { max-width: 320px; max-height: 180px; }

        .objective-columns { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .objective-columns > tbody > tr > td { width: 50%; padding: 0 10px; vertical-align: top; }
        .objective-columns > tbody > tr > td:first-child { padding-left: 0; border-right: 1px solid #777; }
        .objective-columns > tbody > tr > td:last-child { padding-right: 0; }
        .objective-question { font-size: 8.2pt; line-height: 1.25; }
        .options-table { width: 100%; border-collapse: collapse; margin-top: 2px; margin-bottom: 3px; }
        .options-table td { padding: 1px 0; vertical-align: top; }
        .option-line { padding-left: 15px; text-indent: -15px; }
        .option-label { font-weight: bold; }
        .katex { font-size: 1em; }
        .katex-display { margin: 4px 0; text-align: center; }
        .pdf-math-fallback { font-family: 'DejaVu Sans Mono', monospace; }

        .footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #ccc; text-align: center; font-size: 7.5pt; color: #777; }
        .end { margin-top: 12px; text-align: center; font-size: 8pt; font-weight: bold; color: #084117; }

        /* Cover Page Styles */
        .cover-page {
            border: 1px solid #8a8a8a;
            padding: 24px 34px;
            height: 248mm;
            position: relative;
            page-break-after: always;
            box-sizing: border-box;
        }
        .cover-logo-area {
            position: absolute;
            top: 20px;
            left: 34px;
            right: 34px;
            text-align: center;
            white-space: nowrap;
        }
        .cover-logo {
            height: 41px;
            vertical-align: middle;
            margin-right: 7px;
        }
        .cover-school {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 30pt;
            font-weight: 700;
            text-transform: uppercase;
            vertical-align: middle;
            letter-spacing: 0;
            color: #000;
        }
        .cover-unit {
            position: absolute;
            top: 88px;
            left: 34px;
            right: 34px;
            text-align: center;
            font-size: 20pt;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: 0;
            color: #000;
        }
        .cover-test-title {
            position: absolute;
            top: 190px;
            left: 34px;
            right: 34px;
            font-family: 'DejaVu Sans', sans-serif;
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 0;
            color: #000;
        }
        .cover-session {
            position: absolute;
            top: 228px;
            left: 34px;
            right: 34px;
            font-family: 'DejaVu Sans', sans-serif;
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 0;
            color: #000;
        }
        .cover-details {
            position: static;
        }
        .cover-detail-item {
            position: absolute;
            left: 34px;
            right: 34px;
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            margin: 0;
            color: #000;
        }
        .cover-detail-item:nth-child(1) { top: 325px; }
        .cover-detail-item:nth-child(2) { top: 405px; }
        .cover-detail-item:nth-child(3) { top: 480px; }
        .cover-detail-item:nth-child(4) { top: 555px; }
        .cover-detail-item:nth-child(5) { top: 630px; }
        .cover-detail-item:nth-child(6) { top: 700px; }
        .cover-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            vertical-align: bottom;
            margin-left: 3px;
        }
        .cover-score {
            position: absolute;
            right: 78px;
            bottom: 45px;
            width: 190px;
            height: 82px;
        }
        .cover-score-label {
            position: absolute;
            left: 0;
            top: 31px;
            font-size: 15pt;
            font-weight: bold;
            color: #000;
        }
        .cover-score-box {
            position: absolute;
            top: 0;
            right: 0;
            border: 1px solid #777;
            width: 110px;
            height: 82px;
            background: #fff;
        }
    </style>
</head>
<body>

@php $lbl = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary'][$level] ?? $level; @endphp

<!-- Cover Page -->
<div class="cover-page">
    <div class="cover-logo-area">
        <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="cover-logo" />
        <span class="cover-school">CHRISLAND SCHOOLS</span>
    </div>

    <div class="cover-unit">TESTS, EXAMINATIONS AND<br>ACADEMIC RECORDS UNIT</div>

    <div class="cover-test-title"><strong>{{ strtoupper($title) }}</strong></div>
    <div class="cover-session"><strong>{{ $academicSession }} ACADEMIC SESSION</strong></div>

    <div class="cover-details">
        <div class="cover-detail-item">SUBJECT: {{ strtoupper($subject) }}</div>
        <div class="cover-detail-item">LEVEL: {{ strtoupper($classLevel ?: $lbl) }}</div>
        <div class="cover-detail-item">
            CLASS: <span class="cover-line" style="width: 92px;"></span>
        </div>
        <div class="cover-detail-item">DURATION: {{ strtoupper($duration ?: '_________________') }}</div>
        <div class="cover-detail-item">
            DATE: <span class="cover-line" style="width: 320px;"></span>
        </div>
        <div class="cover-detail-item">
            NAME: <span class="cover-line" style="width: 420px;"></span>
        </div>
    </div>

    <div class="cover-score">
        <span class="cover-score-label">SCORE</span>
        <div class="cover-score-box"></div>
    </div>
</div>

<!-- Exam Content -->
<div class="paper-header">
    <div class="paper-brand">
        <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="paper-logo" />
        <span class="paper-school">CHRISLAND SCHOOLS</span>
    </div>
    <div class="paper-title">{{ strtoupper($title) }}</div>
    <div class="paper-session">{{ $academicSession }} ACADEMIC SESSION</div>
    <div class="paper-subject">SUBJECT: {{ strtoupper($subject) }}</div>
    <table class="paper-meta">
        <tr>
            <td>CLASS: {{ strtoupper($classLevel ?: $lbl) }}</td>
            <td>DURATION: {{ strtoupper($duration ?: '_________________') }}</td>
        </tr>
    </table>
</div>

<div class="instructions">
    {{ $instructions }}
</div>

@foreach($questionSections as $sectionIndex => $section)
    <div class="exam-section {{ $sectionIndex > 0 ? 'page-break' : '' }}">
        <div class="section-title">{{ $section['label'] }} ({{ strtoupper($section['title']) }})</div>
        <div class="section-note">INSTRUCTION: {{ $section['note'] }}</div>
        @if($section['type'] === 'mcq')
            @php
                $objectiveQuestions = $section['questions']->values();
                $objectiveSplit = (int) ceil($objectiveQuestions->count() / 2);
                $objectiveColumns = [
                    $objectiveQuestions->take($objectiveSplit)->values(),
                    $objectiveQuestions->slice($objectiveSplit)->values(),
                ];
            @endphp
            <table class="objective-columns">
                <tr>
                    @foreach($objectiveColumns as $columnQuestions)
                        <td>
                            @foreach($columnQuestions as $columnIndex => $q)
                                @php
                                    $questionIndex = $loop->parent->index === 0 ? $columnIndex : $objectiveSplit + $columnIndex;
                                @endphp
                                <div class="question-item objective-question">
                                    <div class="question-row">
                                        <div class="question-no">{{ $section['start'] + $questionIndex }}.</div>
                                        <div class="question-body">
                                            {!! $q->printableHtml() !!}
                                            @if($src = $q->imagePdfSource())
                                                <div class="question-image"><img src="{{ $src }}" alt="Question image" /></div>
                                            @endif
                                            <table class="options-table">
                                                @foreach($q->options->values() as $oi => $opt)
                                                    <tr>
                                                        <td>
                                                            <div class="option-line">
                                                                <span class="option-label">{{ chr(65 + $oi) }}.</span> {!! \App\Support\RichContent::pdf($opt->content) !!}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            </table>
        @else
            <div class="question-list">
                @foreach($section['questions'] as $index => $q)
                    @php $marks = collect($q->marking_scheme)->sum('weight'); @endphp
                    <div class="question-item">
                        <div class="question-row">
                            <div class="question-no">{{ $section['start'] + $index }}.</div>
                            <div class="question-body">
                                {!! $q->printableHtml() !!}
                                <span class="marks">[{{ $marks }}]</span>
                                @if($src = $q->imagePdfSource())
                                    <div class="question-image"><img src="{{ $src }}" alt="Question image" /></div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

<div class="end">END OF EXAMINATION</div>
<div class="footer">Chrisland Schools • {{ $date }}</div>
</body>
</html>
