<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Marking Guide</title>
    <style>
        @page { margin: 20mm 18mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.5; }
        .confidential { text-align: center; font-size: 10pt; color: #c00; font-weight: bold; border: 2px solid #c00; padding: 5px; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 2px; }
        .header { text-align: center; margin-bottom: 14px; }
        .header .school { font-size: 14pt; font-weight: bold; color: #084117; text-transform: uppercase; }
        .header .divider { border-top: 2px solid #084117; margin: 6px 0; }
        .header .exam-title { font-size: 12pt; font-weight: bold; margin: 4px 0; }
        .header .exam-meta { font-size: 8.5pt; color: #555; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 16px 0 6px; padding: 5px 0; border-bottom: 2px solid #084117; color: #084117; text-transform: uppercase; }
        .q-block { margin: 8px 0; page-break-inside: avoid; }
        .q-block .q-text { font-weight: bold; margin-bottom: 4px; }
        .ms-table { width: 100%; border-collapse: collapse; margin: 4px 0; font-size: 9.5pt; }
        .ms-table th { background: #084117; color: #fff; padding: 4px 8px; text-align: left; font-size: 9pt; }
        .ms-table td { padding: 3px 8px; border-bottom: 1px solid #ddd; }
        .ms-table tr:nth-child(even) td { background: #f9f9f9; }
        .ms-table .weight { width: 70px; text-align: center; font-weight: bold; color: #084117; }
        .ms-table .total td { font-weight: bold; border-top: 2px solid #084117; background: #f0f7f0 !important; }
        .footer { text-align: center; font-size: 7.5pt; color: #999; margin-top: 24px; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>

@php $lm = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary']; @endphp

<div class="confidential">Confidential &mdash; For Examiners Only</div>

<div class="header">
    <div class="school">Chrisland Schools</div>
    <div class="divider"></div>
    <div class="exam-title">{{ $title }} &mdash; Marking Guide</div>
    <div class="exam-meta">{{ $subject }} &mdash; {{ $lm[$level] ?? $level }} Level</div>
</div>

@if($theory->isNotEmpty())
    @foreach($theory as $index => $q)
        @php $qm = collect($q->marking_scheme)->sum('weight'); @endphp
        <div class="q-block">
            <div class="q-text">{{ $mcqs->count() + $index + 1 }}. {!! nl2br(e($q->content)) !!} [{{ $qm }} marks]</div>

            @if($q->marking_scheme)
                <table class="ms-table">
                    <tr><th>Expected Points</th><th class="weight">Marks</th></tr>
                    @foreach($q->marking_scheme as $point)
                        <tr><td>{{ $point['point'] }}</td><td class="weight">{{ $point['weight'] }}</td></tr>
                    @endforeach
                    <tr class="total"><td>Total</td><td class="weight">{{ $qm }}</td></tr>
                </table>
            @else
                <p style="color:#999;margin-left:16px;font-size:9pt;">No marking scheme provided.</p>
            @endif
        </div>
    @endforeach
@else
    <p style="color:#999;">No theory questions in this exam.</p>
@endif

<div class="footer">Confidential &bull; Chrisland Schools &bull; {{ $date }}</div>
</body>
</html>
