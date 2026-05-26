<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Preview: {{ $title }}</title>
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

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo {
            max-height: 44px;
            margin-bottom: 4px;
        }

        .school {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--brand);
            letter-spacing: 1px;
        }

        .exam-title {
            font-size: 12pt;
            font-weight: 700;
            margin-top: 3px;
        }

        .meta {
            margin-top: 6px;
            font-size: 8.5pt;
            color: #4b5563;
        }

        .rule {
            border-top: 1.5px solid var(--brand);
            margin: 8px 0 10px;
        }

        .candidate-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .candidate-table td {
            padding: 5px 6px;
            font-size: 8.5pt;
            border: 1px solid #6b7280;
        }

        .candidate-label {
            width: 110px;
            font-weight: 700;
            background: var(--panel);
        }

        .instructions {
            margin-bottom: 10px;
            font-size: 8.5pt;
        }

        .instructions strong {
            color: var(--brand);
        }

        .section-title {
            margin: 12px 0 4px;
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--brand);
        }

        .section-note {
            margin: 0 0 8px;
            font-size: 8.5pt;
            color: #4b5563;
        }

        .theory-section {
            page-break-before: always;
        }

        .question-list {
            margin: 0;
            padding: 0;
        }

        .question-item {
            margin: 0 0 9px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .question-row {
            display: table;
            width: 100%;
        }

        .question-no {
            display: table-cell;
            width: 22px;
            font-weight: 700;
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

        .options-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        .options-table td {
            width: 50%;
            padding: 2px 8px 2px 0;
            vertical-align: top;
        }

        .option-line {
            padding-left: 14px;
            text-indent: -14px;
        }

        .option-label {
            font-weight: 700;
        }

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
            <div class="header">
                <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="logo" />
                <div class="school">Chrisland Schools</div>
                <div class="exam-title">{{ $title }}</div>
                <div class="meta">{{ $subject }} | {{ $lbl }} Level | {{ $totalMarks }} Marks | {{ $date }}</div>
            </div>

            <div class="rule"></div>

            <table class="candidate-table">
                <tr>
                    <td class="candidate-label">Candidate Name</td>
                    <td></td>
                    <td class="candidate-label" style="width:70px;">Class</td>
                    <td style="width:120px;"></td>
                </tr>
            </table>

            <div class="instructions">
                <strong>Instructions:</strong> {{ $instructions }}
                <br>Answer all questions. Write your answers neatly in the spaces or booklet provided by the invigilator.
            </div>

            @if($mcqs->isNotEmpty())
                <div class="section-title">Section A: Multiple Choice</div>
                <div class="section-note">Choose the correct option from A to D for each question.</div>

                <div class="question-list">
                    @foreach($mcqs as $index => $q)
                        <div class="question-item">
                            <div class="question-row">
                                <div class="question-no">{{ $index + 1 }}.</div>
                                <div class="question-body">
                                    {!! nl2br(e($q->printableContent())) !!}
                                    <span class="marks">[1]</span>
                                    @if($src = $q->imageUrl)
                                        <div class="question-image">
                                            <img src="{{ $src }}" alt="Question image" />
                                        </div>
                                    @endif
                                    <table class="options-table">
                                        <tr>
                                            @foreach($q->options->values() as $oi => $opt)
                                                <td>
                                                    <div class="option-line">
                                                        <span class="option-label">{{ chr(65 + $oi) }}.</span> {{ $opt->content }}
                                                    </div>
                                                </td>
                                                @if($oi % 2 === 1 && $oi !== $q->options->count() - 1)
                                                    </tr><tr>
                                                @endif
                                            @endforeach
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($theory->isNotEmpty())
                <div class="theory-section">
                    <div class="section-title">Section B: Theory</div>
                    <div class="section-note">Answer all questions clearly. Show all necessary workings.</div>

                    <div class="question-list">
                        @foreach($theory as $index => $q)
                            @php $qm = collect($q->marking_scheme)->sum('weight'); @endphp
                            <div class="question-item">
                                <div class="question-row">
                                    <div class="question-no">{{ $mcqs->count() + $index + 1 }}.</div>
                                    <div class="question-body">
                                        {!! nl2br(e($q->printableContent())) !!}
                                        <span class="marks">[{{ $qm }}]</span>
                                        @if($src = $q->imageUrl)
                                            <div class="question-image">
                                                <img src="{{ $src }}" alt="Question image" />
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="end">END OF EXAMINATION</div>
            <div class="footer">Chrisland Schools • {{ $date }}</div>
        </main>
    </div>
</div>
</body>
</html>
