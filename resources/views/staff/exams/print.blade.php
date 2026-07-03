<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Preview: {{ $title }}</title>
    @vite('resources/css/print-katex.css')
    <style>
        :root {
            color-scheme: light;
            --page-padding-x: 14mm;
            --page-padding-y: 15mm;
            --ink: #111827;
            --muted: #6b7280;
            --line: #d1d5db;
            --brand: #0f5a2b;
            --panel: #f4f7f5;
            --surface: #ffffff;
            --shell: #e5efe8;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top, rgba(15, 90, 43, 0.12), transparent 35%),
                linear-gradient(180deg, #edf5ef 0%, #dbe8df 100%);
        }

        .shell {
            min-height: 100vh;
            padding: 24px;
        }

        .toolbar {
            position: sticky;
            top: 16px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto 24px;
            padding: 16px 18px;
            border: 1px solid rgba(17, 24, 39, 0.08);
            border-radius: 22px;
            background: rgba(15, 23, 42, 0.9);
            color: #f9fafb;
            backdrop-filter: blur(18px);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
        }

        .toolbar-copy p,
        .toolbar-copy h1 {
            margin: 0;
        }

        .toolbar-copy p {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(249, 250, 251, 0.65);
        }

        .toolbar-copy h1 {
            margin-top: 4px;
            font-size: 20px;
            font-weight: 700;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #f9fafb;
            color: #111827;
        }

        .btn-secondary {
            border-color: rgba(249, 250, 251, 0.18);
            background: transparent;
            color: #f9fafb;
        }

        .preview-stage {
            width: 100%;
            margin: 0 auto;
        }

        .paper {
            width: 100%;
            margin: 0 auto;
            padding: var(--page-padding-y) var(--page-padding-x) 16mm;
            background: var(--surface);
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.14);
            border-radius: 18px;
        }

        .paper-header {
            margin-bottom: 5px;
            text-align: center;
        }

        .paper-brand {
            white-space: nowrap;
        }

        .paper-logo {
            height: 31px;
            margin-right: 6px;
            vertical-align: middle;
        }

        .paper-school {
            font-size: 19pt;
            font-weight: 700;
            vertical-align: middle;
        }

        .paper-title {
            margin-top: 2px;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .paper-session,
        .paper-subject {
            margin-top: 1px;
            font-size: 10pt;
            font-weight: 700;
        }

        .paper-subject {
            text-transform: uppercase;
        }

        .paper-meta {
            width: 100%;
            margin-top: 2px;
            border-collapse: collapse;
            font-size: 9.5pt;
            font-weight: 700;
        }

        .paper-meta td {
            padding: 0;
            border: none;
        }

        .paper-meta td:first-child {
            text-align: left;
        }

        .paper-meta td:last-child {
            text-align: right;
        }

        .instructions {
            max-width: 94%;
            margin: 3px auto 6px;
            text-align: center;
            font-size: 8.5pt;
            line-height: 1.3;
        }

        .instructions strong {
            color: var(--ink);
        }

        .section-title {
            margin: 6px 0 2px;
            text-align: center;
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--ink);
        }

        .section-note {
            margin: 0 0 6px;
            text-align: center;
            font-size: 8.5pt;
            font-weight: 700;
            color: #1f2937;
        }

        .exam-section.page-break {
            page-break-before: always;
        }

        .question-list {
            margin: 0;
            padding: 0;
        }

        .question-item {
            margin: 0 0 7px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .question-row {
            display: table;
            width: 100%;
        }

        .question-no {
            display: table-cell;
            width: 19px;
            font-weight: 400;
            vertical-align: top;
        }

        .question-body {
            display: table-cell;
            vertical-align: top;
        }

        .marks {
            font-size: 8pt;
            color: #4b5563;
            font-weight: 700;
            white-space: nowrap;
        }

        .question-image {
            margin: 4px 0;
        }

        .question-image img {
            max-width: min(100%, 480px);
            max-height: 280px;
        }

        .objective-columns {
            column-count: 2;
            column-gap: 20px;
            column-rule: 1px solid #6b7280;
        }

        .objective-question {
            display: inline-block;
            width: 100%;
            font-size: 8.2pt;
            line-height: 1.25;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .options-table {
            width: 100%;
            margin-top: 2px;
            margin-bottom: 3px;
            border-collapse: collapse;
        }

        .options-table td {
            padding: 1px 0;
            vertical-align: top;
        }

        .option-line {
            padding-left: 15px;
            text-indent: -15px;
        }

        .option-label {
            font-weight: 700;
        }

        .katex { font-size: 1em; }
        .katex-display { margin: 4px 0; text-align: center; }
        .pdf-math-fallback { font-family: 'DejaVu Sans Mono', monospace; }

        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 7.5pt;
            color: #6b7280;
        }

        .end {
            margin-top: 12px;
            text-align: center;
            font-size: 8pt;
            font-weight: 700;
            color: var(--brand);
        }

        /* Cover Page Styles */
        .cover-page {
            border: 1px solid #8a8a8a;
            padding: 18pt 25.5pt;
            height: 248mm;
            min-height: 248mm;
            position: relative;
            page-break-after: always;
            break-after: page;
            box-sizing: border-box;
            margin-bottom: 37.5pt;
            display: block;
        }
        .cover-logo-area {
            position: absolute;
            top: 15pt;
            left: 25.5pt;
            right: 25.5pt;
            text-align: center;
            white-space: nowrap;
        }
        .cover-logo {
            height: 30.75pt;
            vertical-align: middle;
            margin-right: 5.25pt;
        }
        .cover-school {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 30pt;
            font-weight: 800;
            text-transform: uppercase;
            vertical-align: middle;
            letter-spacing: 0;
            color: var(--ink);
        }
        .cover-unit {
            position: absolute;
            top: 66pt;
            left: 25.5pt;
            right: 25.5pt;
            text-align: center;
            font-size: 20pt;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: 0;
            color: var(--ink);
        }
        .cover-test-title {
            position: absolute;
            top: 142.5pt;
            left: 25.5pt;
            right: 25.5pt;
            text-align: center;
            font-size: 18pt;
            font-weight: 700;
            letter-spacing: 0;
            color: var(--ink);
        }
        .cover-session {
            position: absolute;
            top: 171pt;
            left: 25.5pt;
            right: 25.5pt;
            text-align: center;
            font-size: 18pt;
            font-weight: 700;
            letter-spacing: 0;
            color: var(--ink);
        }
        .cover-details {
            position: static;
        }
        .cover-detail-item {
            position: absolute;
            left: 25.5pt;
            right: 25.5pt;
            text-align: center;
            font-size: 15pt;
            font-weight: 700;
            margin: 0;
            color: var(--ink);
        }
        .cover-detail-item:nth-child(1) { top: 243.75pt; }
        .cover-detail-item:nth-child(2) { top: 303.75pt; }
        .cover-detail-item:nth-child(3) { top: 360pt; }
        .cover-detail-item:nth-child(4) { top: 416.25pt; }
        .cover-detail-item:nth-child(5) { top: 472.5pt; }
        .cover-detail-item:nth-child(6) { top: 525pt; }
        .cover-line {
            display: inline-block;
            border-bottom: 1px solid var(--ink);
            vertical-align: bottom;
            margin-left: 2.25pt;
        }
        .cover-score {
            position: absolute;
            right: 58.5pt;
            bottom: 33.75pt;
            width: 142.5pt;
            height: 61.5pt;
        }
        .cover-score-label {
            position: absolute;
            left: 0;
            top: 23.25pt;
            font-size: 15pt;
            font-weight: 700;
            color: var(--ink);
        }
        .cover-score-box {
            position: absolute;
            top: 0;
            right: 0;
            border: 1px solid #777;
            width: 82.5pt;
            height: 61.5pt;
            background: #fff;
        }
        @media print {
            .cover-page {
                border: 1px solid #8a8a8a;
                padding: 18pt 25.5pt;
                height: 248mm;
                margin-bottom: 0;
            }
            .cover-line {
                border-bottom: 1px solid #000;
            }
            .cover-score-box {
                border: 1px solid #777;
            }
        }

        @media (max-width: 768px) {
            .shell {
                padding: 12px;
            }

            .toolbar {
                position: static;
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
            }

            .paper {
                border-radius: 14px;
                padding: 14mm 10mm;
            }

            .options-table,
            .options-table tbody,
            .options-table tr,
            .options-table td {
                display: block;
                width: 100%;
            }

            .question-image img {
                max-width: 100%;
                height: auto;
            }

            .objective-columns {
                column-count: 1;
                column-rule: 0;
            }
        }

        @media print {
            @page {
                margin: 15mm 14mm 16mm;
            }

            html, body {
                background: #fff;
            }

            .shell {
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .paper {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .objective-columns {
                column-count: 2;
                column-rule: 1px solid #6b7280;
            }
        }
    </style>
</head>
<body>
@php
    $examLink = isset($examId) ? route('exams.show', $examId) : url()->previous();
    $lbl = ['SS' => 'Senior Secondary', 'JS' => 'Junior Secondary', 'HP' => 'Higher Primary', 'LP' => 'Lower Primary'][$level] ?? $level;
@endphp

<div class="shell">
    <div class="toolbar">
        <div class="toolbar-copy">
            <p>Question Preview</p>
            <h1>{{ $title }}</h1>
        </div>
        <div class="toolbar-actions">
            <a href="{{ $examLink }}" class="btn btn-secondary">Back to Exam</a>
            <button type="button" onclick="window.print()" class="btn btn-primary">Print Question Paper</button>
        </div>
    </div>

    <div class="preview-stage">
        <main class="paper">
            <!-- Cover Page -->
            <div class="cover-page">
                <div class="cover-logo-area">
                    <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="cover-logo" />
                    <span class="cover-school">CHRISLAND SCHOOLS</span>
                </div>

                <div class="cover-unit">TESTS, EXAMINATIONS AND<br>ACADEMIC RECORDS UNIT</div>

                <div class="cover-test-title"><strong>{{ strtoupper($title) }}</strong></div>
                <div class="cover-session"><strong>{{ $academicSession }} ACADEMIC SESSION</strong></div>

                <div class="cover-details">
                    <div class="cover-detail-item">SUBJECT: {{ strtoupper($subject) }}</div>
                    <div class="cover-detail-item">LEVEL: {{ strtoupper($classLevel ?: $lbl) }}</div>
                    <div class="cover-detail-item">
                        CLASS: <span class="cover-line" style="width: 69pt;"></span>
                    </div>
                    <div class="cover-detail-item">DURATION: {{ strtoupper($duration ?: '_________________') }}</div>
                    <div class="cover-detail-item">
                        DATE: <span class="cover-line" style="width: 240pt;"></span>
                    </div>
                    <div class="cover-detail-item">
                        NAME: <span class="cover-line" style="width: 315pt;"></span>
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
                    <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="paper-logo" />
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
                        <div class="objective-columns">
                            @foreach($section['questions'] as $questionIndex => $q)
                                <div class="question-item objective-question">
                                    <div class="question-row">
                                        <div class="question-no">{{ $section['start'] + $questionIndex }}.</div>
                                        <div class="question-body">
                                            {!! $q->printableHtml() !!}
                                            @if($src = $q->imageUrl)
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
                        </div>
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
                                            @if($src = $q->imageUrl)
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
        </main>
    </div>
</div>
</body>
</html>
