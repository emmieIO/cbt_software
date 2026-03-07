<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->title }} - Official Results</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #1a202c;
            margin: 0;
            padding: 40px;
            background-color: #fff;
        }

        .no-print {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .print-btn {
            background-color: #000;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: opacity 0.2s;
            text-decoration: none;
        }

        .print-btn:hover { opacity: 0.8; }

        .back-btn {
            background-color: #edf2f7;
            color: #4a5568;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }

        .header {
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-align: right;
            margin-bottom: 20px;
        }

        .logo {
            height: 100px;
            width: auto;
        }

        .school-info h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
        }

        .school-info p {
            margin: 5px 0;
            font-size: 12px;
            color: #4a5568;
            font-weight: 600;
            white-space: pre-line;
            line-height: 1.4;
        }

        .report-title {
            text-align: center;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 15px 0;
            margin: 20px 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            font-size: 13px;
        }

        .meta-item {
            font-weight: 700;
            border-bottom: 1px dotted #cbd5e0;
            padding-bottom: 2px;
        }

        .meta-label {
            color: #718096;
            text-transform: uppercase;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.1em;
            display: block;
            margin-bottom: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f7fafc;
            text-align: left;
            padding: 12px 15px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4a5568;
            border-bottom: 2px solid #edf2f7;
        }

        td {
            padding: 12px 15px;
            font-size: 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .rank {
            font-weight: 800;
            color: #718096;
            width: 40px;
        }

        .student-name {
            font-weight: 700;
            color: #1a202c;
        }

        .score-cell {
            font-weight: 800;
            text-align: center;
        }

        .percentage-cell {
            font-weight: 700;
            color: #2d3748;
            text-align: right;
        }

        .grade-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-valid { color: #38a169; }
        .status-violation { color: #e53e3e; }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #718096;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            th { background-color: #f7fafc !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('staff.exams.results.show', $exam->id) }}" class="back-btn">Back to Analytics</a>
        <button onclick="window.print()" class="print-btn">Print Official Report</button>
    </div>

    @php
        $branchKey = $exam->branch->value ?? 'primary';
        $branch = config("app.branches.$branchKey");
    @endphp

    <div class="header">
        <div class="header-content">
            <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Logo" class="logo">
            <div class="school-info">
                <h2>{{ $branch['name'] }}</h2>
                <p>{{ $branch['address'] }}</p>
                <p>TEL: {{ $branch['phones'] }}</p>
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
                <span class="meta-label">Class / Batch</span>
                <span class="meta-item">{{ $exam->schoolClass?->name ?? $exam->prospectiveClass?->name }}</span>
            </div>
            <div>
                <span class="meta-label">Academic Session</span>
                <span class="meta-item">{{ $exam->academicSession?->name }}</span>
            </div>
            <div>
                <span class="meta-label">Total Questions</span>
                <span class="meta-item">{{ $totalQuestions }}</span>
            </div>
            <div>
                <span class="meta-label">Total Candidates</span>
                <span class="meta-item">{{ $exam->attempts->count() }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="rank">SN</th>
                <th>Candidate Name</th>
                <th>Admission ID</th>
                <th style="text-align: center;">Score</th>
                <th style="text-align: right;">Percentage</th>
                <th style="text-align: center;">Security Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exam->attempts as $index => $attempt)
                @php
                    $percentage = $totalQuestions > 0 ? round(($attempt->score / $totalQuestions) * 100) : 0;
                    $hasViolation = (isset($attempt->violations) && count($attempt->violations) > 0) || !empty($attempt->metadata['termination_reason']);
                @endphp
                <tr>
                    <td class="rank">{{ $index + 1 }}</td>
                    <td class="student-name">{{ $attempt->user->name }}</td>
                    <td>{{ $attempt->user->school_id ?? 'N/A' }}</td>
                    <td class="score-cell">{{ $attempt->score }} / {{ $totalQuestions }}</td>
                    <td class="percentage-cell">{{ $percentage }}%</td>
                    <td style="text-align: center;">
                        @if($hasViolation)
                            <span class="grade-badge status-violation">Violation Detected</span>
                        @else
                            <span class="grade-badge status-valid">Validated</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>Generated via {{ config('app.name') }} | {{ date('d M, Y H:i') }}</div>
        <div style="font-weight: 700; text-transform: uppercase;">Official Result Document</div>
    </div>

    <div style="margin-top: 80px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 100px;">
        <div style="border-top: 1px solid #000; text-align: center; padding-top: 10px;">
            <span class="meta-label">Subject Teacher / Invigilator</span>
        </div>
        <div style="border-top: 1px solid #000; text-align: center; padding-top: 10px;">
            <span class="meta-label">Principal's Signature & Stamp</span>
        </div>
    </div>
</body>
</html>