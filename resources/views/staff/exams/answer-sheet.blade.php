<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->title }} - Answer Sheet</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
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
            text-decoration: none;
        }

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
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .school-info p {
            margin: 5px 0;
            font-size: 11px;
            color: #4a5568;
            font-weight: 600;
            white-space: pre-line;
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
            color: #718096;
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
            border-bottom: 1px solid #edf2f7;
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
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('staff.exams.show', $exam->id) }}" class="back-btn">Back to Exam</a>
        <button onclick="window.print()" class="print-btn">Print Answer Sheet</button>
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

    <div class="student-details" style="grid-template-columns: repeat(3, 1fr);">
        <div>
            <span class="label">Subject:</span>
            <div class="field">{{ $exam->subject?->name ?? 'Multi-Subject' }}</div>
        </div>
        <div>
            <span class="label">Class:</span>
            <div class="field">{{ $exam->schoolClass?->name ?? $exam->prospectiveClass?->name }}</div>
        </div>
        <div>
            <span class="label">Date:</span>
            <div class="field">{{ date('d M, Y') }}</div>
        </div>
    </div>

    <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; background: #000; color: #fff; padding: 5px 10px; margin-bottom: 20px;">
        Instructions: Shade the bubble corresponding to the correct option for each question.
    </div>

    <div class="answer-grid">
        @foreach($exam->questions as $index => $question)
            <div class="answer-row">
                <span class="question-num">{{ $index + 1 }}.</span>
                <div class="bubbles">
                    @php $labels = ['A', 'B', 'C', 'D', 'E']; @endphp
                    @foreach($labels as $label)
                        <div class="bubble">{{ $label }}</div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="score-section">
        <div>
            <span class="label">Supervisor's Signature:</span>
            <div class="field" style="margin-top: 30px;"></div>
        </div>
        <div style="text-align: right;">
            <div style="display: inline-block; text-align: left; border: 2px solid #000; padding: 15px; min-width: 150px;">
                <span class="label">Official Score:</span>
                <div style="font-size: 32px; font-weight: 800; text-align: center; margin-top: 10px;">
                    / {{ $exam->questions->count() }}
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px; text-align: center; font-size: 10px; color: #718096;">
        Answer Sheet generated via {{ config('app.name') }} | {{ date('d M, Y H:i') }}
    </div>
</body>
</html>