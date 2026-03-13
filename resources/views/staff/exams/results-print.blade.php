<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Results: {{ $exam->title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 30px;
            color: #000;
        }

        /* Top Action Bar - Non-blocking */
        .no-print {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 15px;
        }

        .print-btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            cursor: pointer;
        }

        .back-btn {
            background: #fff;
            color: #000;
            border: 1px solid #000;
            padding: 10px 20px;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
            text-decoration: none;
        }

        .header {
            padding-bottom: 20px;
            border-bottom: 3px double #000;
            margin-bottom: 25px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .school-info {
            text-align: right;
        }

        .school-info h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .school-info p {
            margin: 3px 0 0;
            font-size: 11px;
            color: #000;
            font-weight: 600;
        }

        .logo {
            height: 90px;
            width: auto;
        }

        .report-title {
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            border: 1px solid #000;
            padding: 15px;
        }

        .meta-label {
            display: block;
            font-size: 9px;
            font-weight: 800;
            color: #444;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .meta-item {
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #f2f2f2;
            padding: 12px 10px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #000;
            text-align: left;
        }

        td {
            padding: 12px 10px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #000;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #000;
            font-weight: 600;
            border-top: 1px solid #000;
            padding-top: 20px;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $school = $exam->school;
    @endphp

    <div class="no-print">
        <a href="{{ route('staff.exams.results.show', $exam->id) }}" class="back-btn">← Back to Analytics</a>
        <button onclick="window.print()" class="print-btn">Print Official Report</button>
    </div>

    <div class="header">
        <div class="header-content">
            <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Logo" class="logo">
            <div class="school-info">
                <h2>{{ $school->name ?? 'Chrisland Schools' }}</h2>
                <p>{{ $school->address ?? 'Lagos, Nigeria' }}</p>
                @if($school && $school->contact_phone)
                    <p>TEL: {{ is_array($school->contact_phone) ? implode(', ', $school->contact_phone) : $school->contact_phone }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="report-title">
        Official Examination Result Sheet
    </div>

    <div class="meta-grid">
        <div>
            <span class="meta-label">Examination</span>
            <span class="meta-item">{{ $exam->title }}</span>
        </div>
        <div>
            <span class="meta-label">Subject</span>
            <span class="meta-item">{{ $exam->subject?->name ?? 'Multi-Subject' }}</span>
        </div>
        <div>
            <span class="meta-label">Academic Session</span>
            <span class="meta-item">{{ $exam->academicSession->name ?? 'Current Session' }}</span>
        </div>
        <div>
            <span class="meta-label">Class / Target</span>
            <span class="meta-item">{{ $exam->schoolClass?->name ?? $exam->prospectiveClass?->name }}</span>
        </div>
        <div>
            <span class="meta-label">Total Questions</span>
            <span class="meta-item">{{ $totalQuestions }} Items</span>
        </div>
        <div>
            <span class="meta-label">Date Generated</span>
            <span class="meta-item">{{ now()->format('d M, Y') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">S/N</th>
                <th>Candidate Name</th>
                <th>ID Number</th>
                <th>Time Spent</th>
                <th>Score</th>
                <th style="text-align: right;">Percentage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exam->attempts as $index => $attempt)
                @php
                    $percentage = ($totalQuestions > 0) ? ($attempt->score / $totalQuestions) * 100 : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: 800;">{{ $attempt->user->name }}</div>
                        <div style="font-size: 10px; color: #444;">{{ $attempt->user->schoolClass?->name ?? 'External' }}</div>
                    </td>
                    <td><span style="font-family: monospace; font-weight: 700;">{{ $attempt->user->username }}</span></td>
                    <td>{{ $attempt->metadata['time_spent_formatted'] ?? 'N/A' }}</td>
                    <td>
                        <span style="font-weight: 800;">{{ $attempt->score }} / {{ $totalQuestions }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 800;">
                        {{ number_format($percentage, 1) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This is an electronically generated report. Verification code: {{ substr($exam->id, 0, 8) }}</p>
        <p>© {{ date('Y') }} Chrisland Schools CBT Portal</p>
    </div>
</body>
</html>
