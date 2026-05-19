<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18mm 16mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; color: #1a1a1a; line-height: 1.5; }

        .header { text-align: center; margin-bottom: 16px; border-bottom: 3px solid #084117; padding-bottom: 14px; }
        .header .logo { max-height: 50px; margin-bottom: 6px; }
        .header .school { font-size: 13pt; font-weight: bold; color: #084117; text-transform: uppercase; letter-spacing: 1.5px; }
        .header .exam-title { font-size: 11pt; font-weight: bold; margin: 4px 0 2px; }
        .header .exam-meta { font-size: 8pt; color: #555; }
        .header .exam-meta span { display: inline-block; padding: 0 6px; }
        .header .exam-meta .sep { color: #084117; font-weight: bold; }

        .candidate-info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .candidate-info td { border: 1px solid #555; padding: 3px 6px; font-size: 8.5pt; color: #333; }
        .candidate-info .label { width: 100px; background: #e8f0e8; font-weight: bold; color: #084117; font-size: 8pt; }

        .section-title { font-size: 10pt; font-weight: bold; margin: 14px 0 6px; padding: 4px 0; border-bottom: 2px solid #084117; color: #084117; text-transform: uppercase; letter-spacing: 0.5px; }

        .instructions { border: 1px solid #084117; padding: 7px 10px; margin-bottom: 12px; font-size: 8.5pt; background: #f5faf5; }
        .instructions strong { color: #084117; }

        .warn { background: #fff8e6; border: 1px solid #e6c300; padding: 5px 8px; font-size: 8pt; color: #665000; margin-bottom: 10px; text-align: center; }

        .question { margin: 7px 0; page-break-inside: avoid; }
        .question .q-text { margin-bottom: 2px; }
        .question .marks { font-size: 8.5pt; color: #666; }

        .options { margin: 2px 0 2px 22px; font-size: 10pt; }
        .options .opt { margin: 1px 0; }

        .footer { text-align: center; font-size: 7pt; color: #999; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 5px; }
        .end-marker { text-align: center; font-size: 8pt; font-weight: bold; color: #084117; margin-top: 20px; padding-top: 10px; border-top: 1px solid #084117; }
    </style>
</head>
<body>

@php $lbl = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary'][$level] ?? $level; @endphp

<div class="header">
    <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="logo" />
    <div class="school">Chrisland Schools</div>
    <div class="exam-title">{{ $title }}</div>
    <div class="exam-meta">
        <span>{{ $subject }}</span>
        <span class="sep">|</span>
        <span>{{ $lbl }} Level</span>
        <span class="sep">|</span>
        <span>{{ $totalMarks }} marks</span>
        <span class="sep">|</span>
        <span>{{ $date }}</span>
    </div>
</div>

<table class="candidate-info">
    <tr>
        <td class="label">Candidate Name:</td>
        <td style="width:200px;"></td>
        <td class="label" style="width:60px;">Class:</td>
        <td style="width:120px;"></td>
    </tr>
</table>

<div class="instructions">
    <strong>Instructions:</strong> {{ $instructions }}
    <br>Answer all questions in the spaces provided on the answer booklet.
    <br>Do NOT write on this question paper.
</div>

<div class="warn">
    Do not write on this paper. All answers must be written on the separate answer booklet provided.
</div>

@if($mcqs->isNotEmpty())
    <div class="section-title">Section A: Multiple Choice ({{ $mcqTotal }} marks)</div>
    <p style="font-size:8.5pt;color:#555;margin:0 0 6px;">Choose the correct answer. Write the letter (A, B, C or D) in your answer booklet.</p>

    @foreach($mcqs as $index => $q)
        <div class="question">
            <div class="q-text">
                <span style="font-weight:bold;">{{ $index + 1 }}.</span>
                {!! nl2br(e($q->content)) !!}
                <span class="marks">[1]</span>
            </div>
            @if($q->image_path)
                <div style="margin:2px 0;">@php $src = str_starts_with($q->image_path, 'http') ? $q->image_path : public_path(ltrim($q->image_path, '/')); @endphp<img src="{{ $src }}" alt="Image" style="max-width:340px;" /></div>
            @endif
            <div class="options">
                @foreach($q->options as $oi => $opt)
                    <div class="opt">{{ chr(65 + $oi) }}. {{ $opt->content }}</div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif

@if($theory->isNotEmpty())
    <div class="section-title">Section B: Theory ({{ $theoryTotal }} marks)</div>
    <p style="font-size:8.5pt;color:#555;margin:0 0 6px;">Answer all questions in your answer booklet. Show all workings where applicable.</p>

    @foreach($theory as $index => $q)
        @php $qm = collect($q->marking_scheme)->sum('weight'); @endphp
        <div class="question">
            <div class="q-text">
                <span style="font-weight:bold;">{{ $mcqs->count() + $index + 1 }}.</span>
                {!! nl2br(e($q->content)) !!}
                <span class="marks">[{{ $qm }}]</span>
            </div>
            @if($q->image_path)
                <div style="margin:2px 0;">@php $src = str_starts_with($q->image_path, 'http') ? $q->image_path : public_path(ltrim($q->image_path, '/')); @endphp<img src="{{ $src }}" alt="Image" style="max-width:340px;" /></div>
            @endif
        </div>
    @endforeach
@endif

<div class="end-marker">END OF EXAMINATION</div>
<div class="footer">Chrisland Schools &bull; {{ $date }}</div>
</body>
</html>
