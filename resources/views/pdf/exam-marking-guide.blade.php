<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Marking Guide</title>
    <style>
        @page { margin: 18mm 16mm 18mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #182118; line-height: 1.5; }
        .confidential { text-align: center; font-size: 9pt; color: #b42318; font-weight: bold; border: 1.5px solid #b42318; padding: 5px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1.8px; }
        .header { text-align: center; margin-bottom: 14px; }
        .school { font-size: 13pt; font-weight: bold; color: #084117; text-transform: uppercase; }
        .title { font-size: 11pt; font-weight: bold; margin-top: 4px; }
        .meta { font-size: 8.5pt; color: #596459; margin-top: 3px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 12px 0; }
        .summary td { border: 1px solid #d9e4da; background: #f7faf7; padding: 7px 8px; }
        .summary .label { display: block; font-size: 7.5pt; text-transform: uppercase; color: #647064; margin-bottom: 2px; }
        .summary .value { font-size: 10pt; font-weight: bold; color: #084117; }

        .q-block { margin: 0 0 12px; padding: 10px 12px; border: 1px solid #dde7de; background: #fff; page-break-inside: avoid; }
        .q-text { font-weight: bold; margin-bottom: 8px; }
        .marks { color: #084117; }
        .ms-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 9pt; }
        .ms-table th { background: #084117; color: #fff; padding: 6px 8px; text-align: left; font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.5px; }
        .ms-table td { padding: 6px 8px; border-bottom: 1px solid #dbe4dc; vertical-align: top; }
        .ms-table tr:nth-child(even) td { background: #f8fbf8; }
        .weight { width: 72px; text-align: center; font-weight: bold; color: #084117; }
        .total td { font-weight: bold; border-top: 1.5px solid #084117; background: #eef5ee !important; }
        .footer { margin-top: 18px; padding-top: 6px; border-top: 1px solid #dbe4dc; text-align: center; font-size: 7.5pt; color: #7b857c; }
    </style>
</head>
<body>

@php $lm = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary']; @endphp

<div class="confidential">Confidential - For Examiners Only</div>

<div class="header">
    <div class="school">Chrisland Schools</div>
    <div class="title">{{ $title }} - Marking Guide</div>
    <div class="meta">{{ $subject }} • {{ $lm[$level] ?? $level }} Level • {{ $date }}</div>
</div>

<table class="summary">
    <tr>
        <td>
            <span class="label">Theory Questions</span>
            <span class="value">{{ $theory->count() }}</span>
        </td>
        <td>
            <span class="label">Theory Marks</span>
            <span class="value">{{ $theoryTotal }}</span>
        </td>
        <td>
            <span class="label">Paper Title</span>
            <span class="value">{{ $title }}</span>
        </td>
    </tr>
</table>

@if($theory->isNotEmpty())
    @foreach($theory as $index => $q)
        @php $qm = collect($q->marking_scheme)->sum('weight'); @endphp
        <div class="q-block">
            <div class="q-text">
                {{ $mcqs->count() + $index + 1 }}.
                {!! nl2br(e($q->printableContent())) !!}
                <span class="marks">[{{ $qm }} marks]</span>
            </div>

            @if($q->marking_scheme)
                <table class="ms-table">
                    <tr>
                        <th>Expected Points</th>
                        <th class="weight">Marks</th>
                    </tr>
                    @foreach($q->marking_scheme as $point)
                        <tr>
                            <td>{{ $point['point'] }}</td>
                            <td class="weight">{{ $point['weight'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td>Total</td>
                        <td class="weight">{{ $qm }}</td>
                    </tr>
                </table>
            @else
                <p style="color:#7b857c; font-size:9pt; margin:0;">No marking scheme provided.</p>
            @endif
        </div>
    @endforeach
@else
    <p style="color:#7b857c;">No theory questions in this exam.</p>
@endif

<div class="footer">Confidential • Chrisland Schools • {{ $date }}</div>
</body>
</html>
