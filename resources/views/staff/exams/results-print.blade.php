<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Results: {{ $exam->title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 40px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.03);
            white-space: nowrap;
            pointer-events: none;
            z-index: -1;
            text-transform: uppercase;
            letter-spacing: 10px;
        }

        /* Top Action Bar */
        .no-print {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            transition: all 0.2s;
        }

        .print-btn:hover {
            background: #000;
            transform: translateY(-1px);
        }

        .back-btn {
            color: #4b5563;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header {
            padding-bottom: 25px;
            border-bottom: 4px solid #111827;
            margin-bottom: 30px;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            right: 0;
            height: 1px;
            background: #111827;
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
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .school-info p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #4b5563;
            font-weight: 600;
            max-width: 400px;
            margin-left: auto;
        }

        .logo {
            height: 100px;
            width: auto;
            filter: grayscale(0.2);
        }

        .report-header {
            text-align: center;
            margin: 30px 0;
        }

        .report-title {
            display: inline-block;
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #111827;
            padding: 10px 30px;
            border: 2px solid #111827;
            background: #f9fafb;
        }

        .meta-container {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin-bottom: 40px;
            border-radius: 4px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 10px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .meta-item {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 50px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        th {
            background: #111827;
            color: #fff;
            padding: 14px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid #111827;
            text-align: left;
        }

        td {
            padding: 14px 12px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        tr:nth-child(even) td {
            background: #fcfcfc;
        }

        .signature-section {
            margin-top: 80px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 100px;
            padding: 0 40px;
        }

        .sig-box {
            text-align: center;
            border-top: 1px solid #111827;
            padding-top: 10px;
        }

        .sig-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
            border-top: 1px solid #e5e7eb;
            padding-top: 25px;
        }

        .qr-code {
            position: static;
            width: 60px;
            height: 60px;
            border: 1px solid #e5e7eb;
            padding: 5px;
            opacity: 0.5;
            margin-top: 18px;
            margin-left: auto;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
            th { background-color: #111827 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
            .meta-container { background-color: #f9fafb !important; -webkit-print-color-adjust: exact; }
            tr:nth-child(even) td { background-color: #fcfcfc !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="watermark">CHRISLAND SCHOOLS</div>

    @php
        $school = $exam->school;
    @endphp

    <div class="no-print">
        <a href="{{ route('staff.exams.results.show', $exam->id) }}" class="back-btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Return to Analytics
        </a>
        <button onclick="window.print()" class="print-btn">Generate Official Printout</button>
    </div>

    <div class="header">
        <div class="header-content">
            <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Logo" class="logo">
            <div class="school-info">
                <h2>{{ $school->name ?? 'Chrisland Schools' }}</h2>
                <p>{{ $school->address ?? 'Institutional Assessment Center • Lagos, Nigeria' }}</p>
                <p>TEL: (+234) 01-2345678, 080-CHRISLAND</p>
            </div>
        </div>
    </div>

    <div class="report-header">
        <div class="report-title">
            Broadsheet & Result Ledger
        </div>
    </div>

    <div class="meta-container">
        <div class="meta-grid">
            <div class="meta-group">
                <span class="meta-label">Examination Code</span>
                <span class="meta-item">{{ strtoupper(substr($exam->id, 0, 8)) }} / {{ $exam->type->value ?? 'GEN' }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Subject Area</span>
                <span class="meta-item">{{ $exam->subject?->name ?? 'Integrated Studies' }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Academic Year</span>
                <span class="meta-item">{{ $exam->academicSession->name ?? '2025/2026 Session' }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Target Population</span>
                <span class="meta-item">{{ $exam->schoolClass?->name ?? 'All Candidates' }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Authentication Date</span>
                <span class="meta-item">{{ now()->format('F d, Y') }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">Pos</th>
                <th>Candidate Information</th>
                <th>Institutional ID</th>
                <th>Attempt Info</th>
                <th style="text-align: center;">Score</th>
                <th style="text-align: right;">Grade (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exam->attempts as $index => $attempt)
                @php
                    $percentage = ($totalQuestions > 0) ? ($attempt->score / $totalQuestions) * 100 : 0;
                    $scoreDisplay = rtrim(rtrim(number_format((float) $attempt->score, 2, '.', ''), '0'), '.');
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: 800; color: #6b7280;">{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: 800; text-transform: uppercase;">{{ $attempt->user->name }}</div>
                        <div style="font-size: 10px; color: #6b7280; font-weight: 700;">{{ $attempt->user->schoolClass?->name ?? 'External Candidate' }}</div>
                    </td>
                    <td>
                        <span style="font-family: 'JetBrains Mono', monospace; letter-spacing: -0.5px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">
                            {{ $attempt->user->username }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 11px;">
                            <span style="color: #6b7280;">Duration:</span> {{ $attempt->metadata['time_spent_formatted'] ?? 'N/A' }}
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: 800;">
                        {{ $scoreDisplay }} / {{ $totalQuestions }}
                    </td>
                    <td style="text-align: right; font-weight: 900; font-size: 14px;">
                        {{ number_format($percentage, 1) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">Examiner / Invigilator</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">Principal / Academic Director</div>
        </div>
    </div>

    <div class="footer">
        <p>This document is an official record generated by the Chrisland CBT Management System.</p>
        <p>Verification Code: <strong>{{ strtoupper($exam->id) }}</strong> • Printed on {{ now()->format('d/m/Y H:i:s') }}</p>
        <p style="margin-top: 10px; font-weight: 800; color: #111827;">Omnia Vincit Labor</p>
    </div>

    <div class="qr-code">
        <!-- Placeholder for QR or School Stamp -->
        <div style="font-size: 8px; text-align: center; margin-top: 15px; font-weight: 900;">OFFICIAL STAMP</div>
    </div>
</body>
</html>
