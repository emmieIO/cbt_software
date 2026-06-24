<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Answer Sheet: {{ $exam->title }}</title>
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

        .logo {
            height: 80px;
            width: auto;
        }

        .school-info {
            text-align: right;
        }

        .school-info h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .school-info p {
            margin: 2px 0 0;
            font-size: 10px;
            font-weight: 600;
            color: #000;
        }

        .exam-title {
            text-align: center;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
            margin: 20px 0;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .student-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .field {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-top: 5px;
            min-height: 20px;
        }

        .label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #444;
        }

        .answer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px 30px;
            margin-top: 20px;
        }

        .answer-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .question-num {
            font-weight: 800;
            font-size: 14px;
            min-width: 25px;
        }

        .bubbles {
            display: flex;
            gap: 8px;
        }

        .bubble {
            width: 24px;
            height: 24px;
            border: 1px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .score-section {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .header { border-bottom: 3px double #000; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('staff.exams.show', $exam->id) }}" class="back-btn">← Back to Exam</a>
        <button onclick="window.print()" class="print-btn">Print Answer Sheet</button>
    </div>

    @php
        $school = $exam->school;
    @endphp

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

        <div class="exam-title">
            ANSWER SHEET: {{ $exam->title }}
        </div>
    </div>

    <div class="student-details">
        <div>
            <span class="label">Candidate Name:</span>
            <div class="field"></div>
        </div>
        <div>
            <span class="label">Examination ID / Number:</span>
            <div class="field"></div>
        </div>
    </div>

    <div class="meta-grid" style="display: flex; gap: 40px; margin-bottom: 30px;">
        <div style="flex: 1;">
            <span class="label">Subject:</span>
            <div class="field">{{ $exam->subject?->name ?? 'Multi-Subject' }}</div>
        </div>
        <div style="flex: 1;">
            <span class="label">Class:</span>
            <div class="field">{{ $exam->schoolClass?->name ?? 'General Assessment' }}</div>
        </div>
        <div style="flex: 1;">
            <span class="label">Date:</span>
            <div class="field">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="answer-grid">
        @foreach($exam->questions as $index => $question)
            <div class="answer-row">
                <span class="question-num">{{ $index + 1 }}</span>
                <div class="bubbles">
                    <div class="bubble">A</div>
                    <div class="bubble">B</div>
                    <div class="bubble">C</div>
                    <div class="bubble">D</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="score-section">
        <div>
            <span class="label">Invigilator Name & Signature:</span>
            <div class="field" style="margin-top: 30px;"></div>
        </div>
        <div style="text-align: right;">
            <span class="label">Official Score:</span>
            <div style="font-size: 48px; font-weight: 900; margin-top: 10px; opacity: 0.1;">/ {{ count($exam->questions) }}</div>
        </div>
    </div>
</body>
</html>
