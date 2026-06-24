<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - Marking Guide</title>
    <style>
        {!! file_get_contents(base_path('node_modules/katex/dist/katex.min.css')) ?: '' !!}
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #111;
        }
        .sheet {
            min-height: 260mm;
            padding: 7mm 8mm;
            border: 1px solid #777;
        }
        .confidential {
            margin-bottom: 3mm;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .brand {
            margin-bottom: 5mm;
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
        .meta-table {
            width: 100%;
            margin-bottom: 4mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .meta-table td {
            height: 7mm;
            padding: 1mm 2mm;
            border: 1px solid #888;
            font-size: 8.5pt;
        }
        .meta-label {
            width: 19mm;
            font-weight: bold;
            white-space: nowrap;
        }
        .meta-value {
            font-weight: bold;
            text-transform: uppercase;
        }
        .guide-title {
            margin: 1mm 0 1mm;
            text-align: center;
            font-family: 'DejaVu Serif', serif;
            font-size: 17pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .guide-note {
            width: 86%;
            margin: 0 auto 5mm;
            text-align: center;
            font-size: 7.5pt;
        }
        .question-block {
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }
        .question-header {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .question-header td {
            padding: 2mm;
            border: 1px solid #777;
            vertical-align: top;
        }
        .question-number {
            width: 15%;
            font-weight: bold;
            text-align: center;
        }
        .question-text {
            width: 62%;
            font-weight: bold;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .question-marks {
            width: 23%;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }
        .scheme-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .scheme-table th,
        .scheme-table td {
            padding: 1.5mm 2mm;
            border: 1px solid #888;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .scheme-table th {
            font-size: 8pt;
            text-align: left;
            text-transform: uppercase;
        }
        .scheme-index {
            width: 8%;
            text-align: center;
        }
        .scheme-point {
            width: 77%;
        }
        .scheme-marks {
            width: 15%;
            font-weight: bold;
            text-align: center;
        }
        .scheme-total td {
            font-weight: bold;
        }
        .missing-scheme {
            padding: 3mm;
            border: 1px solid #888;
            border-top: none;
            font-size: 8pt;
        }
        .approval-table {
            width: 100%;
            margin-top: 7mm;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
        }
        .approval-table td {
            width: 50%;
            padding: 6mm 3mm 1mm;
            border: 1px solid #888;
            font-size: 8pt;
            font-weight: bold;
        }
        .empty-state {
            padding: 20mm 0;
            text-align: center;
            font-size: 10pt;
        }
        .katex { font-size: 1em; }
        .katex-display { margin: 3px 0; text-align: center; }
        .pdf-math-fallback { font-family: 'DejaVu Sans Mono', monospace; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('assets/img/chrisland-school-logo.png');
    $logoSource = is_file($logoPath)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
        : null;
@endphp

<div class="sheet">
    <div class="confidential">Confidential - For Examiners Only</div>

    <div class="brand">
        @if($logoSource)
            <img src="{{ $logoSource }}" alt="Chrisland Schools" class="brand-logo" />
        @endif
        <span class="brand-name">CHRISLAND SCHOOLS</span>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">EXAM:</td>
            <td colspan="3" class="meta-value">{{ $title }}</td>
        </tr>
        <tr>
            <td class="meta-label">SUBJECT:</td>
            <td class="meta-value">{{ $subject }}</td>
            <td class="meta-label">CLASS:</td>
            <td class="meta-value">{{ $classLevel ?: $level }}</td>
        </tr>
        <tr>
            <td class="meta-label">SESSION:</td>
            <td class="meta-value">{{ $academicSession }}</td>
            <td class="meta-label">TOTAL MARKS:</td>
            <td class="meta-value">{{ $theoryTotal }}</td>
        </tr>
    </table>

    <div class="guide-title">Written Examination Marking Guide</div>
    <div class="guide-note">
        Award marks only for the expected points shown below. Accept equivalent wording where the required meaning is clearly demonstrated.
    </div>

    @if($theory->isNotEmpty())
        @foreach($theory as $index => $question)
            @php
                $questionMarks = collect($question->marking_scheme)->sum('weight');
                $questionNumber = $mcqs->count() + $index + 1;
            @endphp
            <div class="question-block">
                <table class="question-header">
                    <colgroup>
                        <col style="width:15%;">
                        <col style="width:62%;">
                        <col style="width:23%;">
                    </colgroup>
                    <tr>
                        <td class="question-number">QUESTION {{ $questionNumber }}</td>
                        <td class="question-text">{!! $question->printableHtml() !!}</td>
                        <td class="question-marks">{{ $questionMarks }} MARKS</td>
                    </tr>
                </table>

                @if($question->marking_scheme)
                    <table class="scheme-table">
                        <colgroup>
                            <col style="width:8%;">
                            <col style="width:77%;">
                            <col style="width:15%;">
                        </colgroup>
                        <tr>
                            <th class="scheme-index">No.</th>
                            <th class="scheme-point">Expected Answer / Marking Point</th>
                            <th class="scheme-marks">Marks</th>
                        </tr>
                        @foreach($question->marking_scheme as $pointIndex => $point)
                            <tr>
                                <td class="scheme-index">{{ $pointIndex + 1 }}</td>
                                <td class="scheme-point">{!! \App\Support\RichContent::pdf($point['point'] ?? '') !!}</td>
                                <td class="scheme-marks">{{ $point['weight'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                        <tr class="scheme-total">
                            <td colspan="2">TOTAL</td>
                            <td class="scheme-marks">{{ $questionMarks }}</td>
                        </tr>
                    </table>
                @else
                    <div class="missing-scheme">No marking scheme has been configured for this question.</div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-state">No written questions in this exam.</div>
    @endif

    <table class="approval-table">
        <tr>
            <td>Prepared by / Signature:</td>
            <td>Verified by / Signature:</td>
        </tr>
    </table>
</div>
</body>
</html>
