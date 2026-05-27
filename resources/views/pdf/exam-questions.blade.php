<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        {!! file_get_contents(base_path('node_modules/katex/dist/katex.min.css')) ?: '' !!}
        @page { margin: 15mm 14mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.45; color: #111; }

        .header { text-align: center; margin-bottom: 10px; }
        .logo { max-height: 44px; margin-bottom: 4px; }
        .school { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #084117; letter-spacing: 1px; }
        .title { font-size: 12pt; font-weight: bold; margin-top: 3px; }
        .meta { margin-top: 6px; font-size: 8.5pt; color: #444; }

        .rule { border-top: 1.5px solid #084117; margin: 8px 0 10px; }

        .candidate-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .candidate-table td { padding: 5px 6px; font-size: 8.5pt; border: 1px solid #777; }
        .candidate-label { width: 110px; font-weight: bold; background: #f2f6f2; }

        .instructions { margin-bottom: 10px; font-size: 8.5pt; }
        .instructions strong { color: #084117; }

        .section-title { margin: 12px 0 4px; font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #084117; }
        .section-note { margin: 0 0 8px; font-size: 8.5pt; color: #555; }
        .theory-section.page-break { page-break-before: always; }

        .question-list { margin: 0; padding: 0; }
        .question-item { margin: 0 0 9px; page-break-inside: avoid; }
        .question-row { display: table; width: 100%; }
        .question-no { display: table-cell; width: 22px; font-weight: bold; vertical-align: top; }
        .question-body { display: table-cell; vertical-align: top; }
        .marks { font-size: 8pt; color: #555; font-weight: bold; white-space: nowrap; }
        .question-image { margin: 4px 0 4px 0; }
        .question-image img { max-width: 320px; max-height: 180px; }

        .options-table { width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 6px; table-layout: fixed; }
        .options-table td { width: 50%; padding: 2px 8px 2px 0; vertical-align: top; }
        .option-line { padding-left: 14px; text-indent: -14px; }
        .option-label { font-weight: bold; }
        .katex { font-size: 1em; }
        .katex-display { margin: 4px 0; text-align: center; }
        .pdf-math-fallback { font-family: 'DejaVu Sans Mono', monospace; }

        .footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #ccc; text-align: center; font-size: 7.5pt; color: #777; }
        .end { margin-top: 12px; text-align: center; font-size: 8pt; font-weight: bold; color: #084117; }
    </style>
</head>
<body>

@php $lbl = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary'][$level] ?? $level; @endphp

<div class="header">
    <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="logo" />
    <div class="school">Chrisland Schools</div>
    <div class="title">{{ $title }}</div>
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
                        {!! $q->printableHtml() !!}
                        <span class="marks">[1]</span>
                        @if($src = $q->imagePdfSource())
                            <div class="question-image">
                                <img src="{{ $src }}" alt="Question image" />
                            </div>
                        @endif
                        <table class="options-table">
                            <tr>
                                @foreach($q->options->values() as $oi => $opt)
                                    <td>
                                        <div class="option-line">
                                            <span class="option-label">{{ chr(65 + $oi) }}.</span> {!! \App\Support\RichContent::pdf($opt->content) !!}
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
    <div class="theory-section {{ $mcqs->isNotEmpty() ? 'page-break' : '' }}">
        <div class="section-title">Section B: Theory</div>
        <div class="section-note">Answer all questions clearly. Show all necessary workings.</div>

        <div class="question-list">
        @foreach($theory as $index => $q)
            @php $qm = collect($q->marking_scheme)->sum('weight'); @endphp
            <div class="question-item">
                <div class="question-row">
                    <div class="question-no">{{ $mcqs->count() + $index + 1 }}.</div>
                    <div class="question-body">
                        {!! $q->printableHtml() !!}
                        <span class="marks">[{{ $qm }}]</span>
                        @if($src = $q->imagePdfSource())
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
</body>
</html>
