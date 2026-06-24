<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Result Slip - {{ $student->name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Figtree:wght@400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 50px;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 70px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.02);
            white-space: nowrap;
            pointer-events: none;
            z-index: -1;
            text-transform: uppercase;
            letter-spacing: 15px;
        }

        .no-print {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }

        .print-btn {
            background: #111827;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 8px;
        }

        .header {
            text-align: center;
            border-bottom: 5px double #111827;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }

        .logo {
            height: 110px;
            margin-bottom: 15px;
        }

        .school-name {
            font-family: 'Cinzel', serif;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .school-address {
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            margin: 5px 0;
        }

        .document-title {
            text-align: center;
            margin: 30px 0;
        }

        .document-title h1 {
            display: inline-block;
            border: 2px solid #111827;
            padding: 10px 40px;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 4px;
            background: #f9fafb;
        }

        .student-profile {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            padding: 25px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 8px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px dashed #d1d5db;
            padding-bottom: 5px;
        }

        .info-label {
            font-size: 11px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            width: 140px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .performance-summary {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 50px;
        }

        .stat-card {
            text-align: center;
            padding: 20px 40px;
            border: 2px solid #111827;
            min-width: 150px;
        }

        .stat-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 900;
            color: #111827;
        }

        .subject-results {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 60px;
        }

        .subject-results th {
            background: #111827;
            color: #fff;
            text-align: left;
            padding: 15px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .subject-results td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
        }

        .grade-badge {
            font-weight: 900;
            font-size: 18px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            margin-top: 80px;
            padding: 0 50px;
        }

        .sig-line {
            border-top: 1px solid #111827;
            text-align: center;
            padding-top: 10px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
            th { background-color: #111827 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
            .student-profile { background-color: #f9fafb !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="watermark">OFFICIAL TRANSCRIPT</div>

    <div class="no-print">
        <button onclick="window.history.back()" style="background: none; border: none; font-weight: 700; cursor: pointer;">← Back</button>
        <button onclick="window.print()" class="print-btn">Print Official Result Slip</button>
    </div>

    <div class="header">
        <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" class="logo" alt="Logo">
        <h1 class="school-name">Chrisland Schools</h1>
        <p class="school-address">Excellence in Education • Lagos, Nigeria</p>
        <p class="school-address">Examination & Assessment Unit</p>
    </div>

    <div class="document-title">
        <h1>Official Examination Result Slip</h1>
    </div>

    <div class="student-profile">
        <div class="col">
            <div class="info-row">
                <span class="info-label">Candidate Name</span>
                <span class="info-value">{{ strtoupper($student->name) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Institutional ID</span>
                <span class="info-value">{{ $student->username }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Academic Class</span>
                <span class="info-value">{{ $exam->schoolClass?->name ?? 'Integrated Level' }}</span>
            </div>
        </div>
        <div class="col">
            <div class="info-row">
                <span class="info-label">Exam Title</span>
                <span class="info-value">{{ $exam->title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Academic Year</span>
                <span class="info-value">{{ $exam->academicSession?->name ?? '2025/2026' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Term / Period</span>
                <span class="info-value">{{ strtoupper($exam->term ?? 'First') }} Term</span>
            </div>
        </div>
    </div>

    <div class="performance-summary">
        <div class="stat-card" style="background: #111827; color: #fff;">
            <div class="stat-label" style="color: #9ca3af;">Grade (%)</div>
            @php
                $percentage = ($totalQuestions > 0) ? ($attempt->score / $totalQuestions) * 100 : 0;
            @endphp
            <div class="stat-value" style="color: #fff;">{{ number_format($percentage, 1) }}%</div>
        </div>
    </div>

    <table class="subject-results">
        <thead>
            <tr>
                <th>Assessment Component</th>
                <th>Category</th>
                <th style="text-align: center;">Items</th>
                <th style="text-align: right;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $exam->subject?->name ?? 'Integrated Studies' }}</td>
                <td>{{ strtoupper($exam->type->value) }}</td>
                <td style="text-align: center;">{{ $totalQuestions }}</td>
                <td style="text-align: right; color: {{ $percentage >= 50 ? '#059669' : '#dc2626' }}">
                    {{ $percentage >= 50 ? 'PASSED' : 'RE-ASSESS' }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="signature-grid">
        <div class="sig-box">
            <div style="height: 40px;"></div>
            <div class="sig-line">Registrar / Exams Officer</div>
        </div>
        <div class="sig-box">
            <div style="height: 40px;"></div>
            <div class="sig-line">School Principal</div>
        </div>
    </div>

    <div class="footer">
        <p>This result slip is an official document of Chrisland Schools. Any alteration renders it void.</p>
        <p>Verification Hash: {{ strtoupper(sha1($attempt->id)) }} • Printed on {{ now()->format('d/m/Y H:i') }}</p>
        <p style="margin-top: 15px; font-weight: 900; color: #111827; letter-spacing: 2px;">OMNIA VINCIT LABOR</p>
    </div>
</body>
</html>
