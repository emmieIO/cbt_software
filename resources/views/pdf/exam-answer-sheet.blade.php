<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - MCQ Answer Sheet</title>
    <style>
        @page { margin: 15mm 14mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; color: #101510; }
        .header { text-align: center; margin-bottom: 8px; }
        .logo { max-height: 42px; margin-bottom: 4px; }
        .school { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #084117; letter-spacing: 1px; }
        .title { font-size: 11.5pt; font-weight: bold; margin-top: 3px; }
        .meta { margin-top: 5px; font-size: 8.5pt; color: #485248; }
        .rule { border-top: 1.5px solid #084117; margin: 8px 0 10px; }

        .candidate-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .candidate-table td { padding: 6px 7px; font-size: 8.5pt; border: 1px solid #777; }
        .candidate-label { width: 110px; font-weight: bold; background: #f2f6f2; }

        .instructions { margin-bottom: 10px; font-size: 8.5pt; }
        .instructions strong { color: #084117; }

        .sheet-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet-table th { background: #084117; color: #fff; padding: 6px 4px; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4px; }
        .sheet-table td { border-bottom: 1px solid #dbe4dc; padding: 6px 4px; text-align: center; font-size: 8.8pt; }
        .sheet-table tr:nth-child(even) td { background: #f8fbf8; }
        .q-no { width: 14%; font-weight: bold; color: #084117; }
        .bubble { display: inline-block; width: 16px; height: 16px; border: 1.5px solid #084117; border-radius: 999px; }

        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #d0d7d0; text-align: center; font-size: 7.5pt; color: #777; }
    </style>
</head>
<body>

@php $lm = ['SS' => 'Senior Secondary', 'JS' => 'Junior Secondary', 'HP' => 'Higher Primary', 'LP' => 'Lower Primary']; @endphp

<div class="header">
    <img src="{{ public_path('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Schools" class="logo" />
    <div class="school">Chrisland Schools</div>
    <div class="title">{{ $title }} - MCQ Answer Sheet</div>
    <div class="meta">{{ $subject }} | {{ $lm[$level] ?? $level }} Level | {{ $mcqTotal }} Questions | {{ $date }}</div>
</div>

<div class="rule"></div>

<table class="candidate-table">
    <tr>
        <td class="candidate-label">Candidate Name</td>
        <td></td>
        <td class="candidate-label" style="width:80px;">Class</td>
        <td style="width:130px;"></td>
    </tr>
    <tr>
        <td class="candidate-label">Candidate No.</td>
        <td></td>
        <td class="candidate-label">Date</td>
        <td>{{ $date }}</td>
    </tr>
</table>

<div class="instructions">
    <strong>Instructions:</strong> Shade or tick only one option for each question. Do not mark more than one answer for the same question.
</div>

@if($mcqs->isNotEmpty())
    <table class="sheet-table">
        <tr>
            <th class="q-no">No.</th>
            <th>A</th>
            <th>B</th>
            <th>C</th>
            <th>D</th>
            <th class="q-no">No.</th>
            <th>A</th>
            <th>B</th>
            <th>C</th>
            <th>D</th>
        </tr>
        @foreach($mcqs->values()->chunk(2) as $pair)
            <tr>
                @foreach($pair as $index => $q)
                    <td class="q-no">{{ $loop->parent->index * 2 + $index + 1 }}</td>
                    <td><span class="bubble"></span></td>
                    <td><span class="bubble"></span></td>
                    <td><span class="bubble"></span></td>
                    <td><span class="bubble"></span></td>
                @endforeach
                @if($pair->count() < 2)
                    <td class="q-no"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
@else
    <p style="color:#777;">No multiple choice questions in this exam.</p>
@endif

<div class="footer">Chrisland Schools • Candidate MCQ Response Sheet</div>
</body>
</html>
