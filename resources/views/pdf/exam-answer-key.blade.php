<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Answer Key</title>
    <style>
        @page { margin: 20mm 18mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #1a1a1a; }
        .confidential { text-align: center; font-size: 10pt; color: #c00; font-weight: bold; border: 2px solid #c00; padding: 5px; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 2px; }
        .header { text-align: center; margin-bottom: 14px; }
        .header .school { font-size: 14pt; font-weight: bold; color: #084117; text-transform: uppercase; }
        .header .divider { border-top: 2px solid #084117; margin: 6px 0; }
        .header .exam-title { font-size: 12pt; font-weight: bold; margin: 4px 0; }
        .header .exam-meta { font-size: 8.5pt; color: #555; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 16px 0 6px; padding: 5px 0; border-bottom: 2px solid #084117; color: #084117; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        th { background: #084117; color: #fff; padding: 5px 8px; text-align: left; font-size: 9pt; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .ans-num { width: 50px; font-weight: bold; }
        .ans-letter { width: 40px; text-align: center; font-weight: bold; color: #084117; }
        .footer { text-align: center; font-size: 7.5pt; color: #999; margin-top: 24px; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>

@php $lm = ['SS'=>'Senior Secondary','JS'=>'Junior Secondary','HP'=>'Higher Primary','LP'=>'Lower Primary']; @endphp

<div class="confidential">Confidential &mdash; For Examiners Only</div>

<div class="header">
    <div class="school">Chrisland Schools</div>
    <div class="divider"></div>
    <div class="exam-title">{{ $title }} &mdash; Answer Key</div>
    <div class="exam-meta">{{ $subject }} &mdash; {{ $lm[$level] ?? $level }} Level</div>
</div>

<div class="section-title">Answer Key</div>

@if($mcqs->isNotEmpty())
    <table>
        <tr><th class="ans-num">No.</th><th class="ans-letter">Answer</th><th>Option</th></tr>
        @foreach($mcqs as $index => $q)
            @php $correct = $q->options->firstWhere('is_correct', true); @endphp
            <tr>
                <td class="ans-num">{{ $index + 1 }}</td>
                @if($correct)
                    @php $letter = chr(65 + $q->options->search(fn($o) => $o->id === $correct->id)); @endphp
                    <td class="ans-letter" style="color:#084117;">{{ $letter }}</td>
                    <td>{{ $correct->content }}</td>
                @else
                    <td class="ans-letter" style="color:#c00;">N/A</td>
                    <td style="color:#c00;">No answer set</td>
                @endif
            </tr>
        @endforeach
    </table>
@else
    <p style="color:#999;">No multiple choice questions in this exam.</p>
@endif

<div class="footer">Confidential &bull; Chrisland Schools &bull; Quick Reference for Marking Team</div>
</body>
</html>
