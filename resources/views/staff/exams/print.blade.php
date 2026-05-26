<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Examination: {{ $exam->title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff;
            margin: 0;
            padding: 30px;
            color: #000;
            line-height: 1.5;
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

        .exam-title-main {
            text-align: center;
            margin: 20px 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            border: 1px solid #000;
            padding: 15px;
        }

        .meta-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #444;
            display: block;
        }

        .meta-item {
            font-size: 13px;
            font-weight: 700;
        }

        .instructions {
            margin-bottom: 40px;
            padding: 15px;
            border: 1px solid #000;
        }

        .instructions h3 {
            margin: 0 0 5px 0;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .instructions p {
            margin: 0;
            font-size: 12px;
        }

        .question-block {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .question-header {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }

        .question-num {
            font-weight: 900;
            font-size: 16px;
            min-width: 25px;
        }

        .question-text {
            font-weight: 700;
            font-size: 15px;
            flex: 1;
        }

        .question-image {
            margin: 15px 0 15px 37px;
            max-width: 350px;
        }

        .question-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #eee;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            margin-left: 37px;
        }

        .option {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            gap: 8px;
        }

        .option-letter {
            font-weight: 800;
            color: #444;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .header { border-bottom: 3px double #000; }
        }
    </style>
</head>
<body>
    @php
        $school = $exam->school;
    @endphp

    <div class="no-print">
        <a href="{{ route('staff.exams.show', $exam->id) }}" class="back-btn">← Back to Exam</a>
        <button onclick="window.print()" class="print-btn">Print Question Paper</button>
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

    <div class="exam-title-main">
        {{ $exam->title }}
    </div>

    <div class="meta-grid">
        <div>
            <span class="meta-label">Subject(s)</span>
            <span class="meta-item">{{ $exam->subject?->name ?? 'General Assessment' }}</span>
        </div>
        <div>
            <span class="meta-label">Class / Batch</span>
            <span class="meta-item">{{ $exam->schoolClass?->name ?? 'General Assessment' }}</span>
        </div>
        <div>
            <span class="meta-label">Duration</span>
            <span class="meta-item">{{ $exam->duration }} Minutes</span>
        </div>
        <div>
            <span class="meta-label">Session</span>
            <span class="meta-item">{{ $exam->academicSession->name }}</span>
        </div>
    </div>

    <div class="instructions">
        <h3>Instructions:</h3>
        <p>{{ $exam->instructions ?? 'Attempt all questions. Select the most appropriate option for each question.' }}</p>
    </div>

    <div class="questions-container">
        @foreach($exam->questions as $index => $question)
            <div class="question-block">
                <div class="question-header">
                    <span class="question-num">{{ $index + 1 }}.</span>
                    <div class="question-text">
                        {!! nl2br(e($question->content)) !!}
                    </div>
                </div>

                @if($question->image_url)
                    <div class="question-image">
                        <img src="{{ $question->image_url }}" alt="Question Diagram">
                    </div>
                @endif

                <div class="options-grid">
                    @foreach($question->options as $oIndex => $option)
                        <div class="option">
                            <span class="option-letter">{{ chr(65 + $oIndex) }}.</span>
                            <span class="option-content">{{ $option->content }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        End of Examination Paper - {{ $school->name ?? 'Chrisland Schools' }}
    </div>
</body>
</html>
