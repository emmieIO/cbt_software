<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exam->title }} - Hard Copy</title>
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

        .print-btn:hover {
            opacity: 0.8;
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
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px 40px;
            margin-top: 25px;
            text-align: left;
            font-size: 13px;
            border-bottom: 3px double #000;
            padding-bottom: 20px;
        }

        .meta-item {
            font-weight: 700;
            border-bottom: 1px dotted #cbd5e0;
            padding-bottom: 2px;
        }

        .meta-label {
            color: #718096;
            text-transform: uppercase;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.1em;
            display: block;
            margin-bottom: 4px;
        }

        .instructions {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .instructions-title {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 8px;
            display: block;
            text-decoration: underline;
        }

        .questions {
            margin-top: 20px;
        }

        .question {
            margin-bottom: 25px;
            break-inside: avoid;
        }

        .question-header {
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            gap: 12px;
        }

        .question-number {
            min-width: 30px;
            font-weight: 800;
        }

        .question-content {
            flex: 1;
        }

        .options {
            list-style: none;
            padding-left: 42px;
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 20px;
        }

        .option {
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .option-label {
            font-weight: 700;
            min-width: 20px;
        }

        .student-info {
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            font-size: 14px;
        }

        .info-field {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-top: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }

            .header {
                border-bottom: 3px double #000;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('staff.exams.show', $exam->id) }}" class="back-btn">Back to Exam</a>
        <button onclick="window.print()" class="print-btn">Print Now</button>
    </div>

    @php
        $branchKey = $exam->branch->value ?? 'primary';
        $branch = config("app.branches.$branchKey");
    @endphp

    <div class="header">
        <div style="display: flex; align-items: center; justify-content: space-between; text-align: right; margin-bottom: 20px;">
            <img src="{{ asset('assets/img/chrisland-school-logo.png') }}" alt="Chrisland Logo" style="height: 120px; width: auto;">
            <div style="flex: 1; margin-left: 40px;">
                <h2 style="margin: 0; font-size: 26px; font-weight: 800; text-transform: uppercase; color: #000;">{{ $branch['name'] }}</h2>
                <p style="margin: 5px 0 5px 0; font-size: 12px; color: #4a5568; font-weight: 600; white-space: pre-line; line-height: 1.4;">{{ $branch['address'] }}</p>
                <p style="margin: 0; font-size: 12px; color: #4a5568; font-weight: 700; letter-spacing: 0.5px;">TEL: {{ $branch['phones'] }}</p>
            </div>
        </div>
        
        <h1 style="margin: 20px 0 0 0; font-size: 22px; font-weight: 800; border-top: 2px solid #000; padding-top: 15px; text-transform: uppercase; letter-spacing: 1px; text-align: center;">
            {{ $exam->title }}
        </h1>

        <div class="meta-grid">
            <div>
                <span class="meta-label">Subject(s)</span>
                <span class="meta-item">{{ $exam->subject?->name ?? ($exam->type->value === 'entrance' ? 'Multi-subject Entrance' : 'General Assessment') }}</span>
            </div>
            <div>
                <span class="meta-label">Class / Batch</span>
                <span class="meta-item">{{ $exam->schoolClass?->name ?? $exam->prospectiveClass?->name }}</span>
            </div>
            <div>
                <span class="meta-label">Time Allowed</span>
                <span class="meta-item">{{ $exam->duration }} Minutes</span>
            </div>
            <div>
                <span class="meta-label">Academic Session</span>
                <span class="meta-item">{{ $exam->academicSession?->name }}</span>
            </div>
        </div>
    </div>

    <div class="student-info">
        <div>
            <span class="meta-label">Student Name:</span>
            <div class="info-field"></div>
        </div>
        <div>
            <span class="meta-label">Examination ID:</span>
            <div class="info-field"></div>
        </div>
    </div>

    @if($exam->instructions)
        <div class="instructions">
            <span class="instructions-title">Instructions</span>
            {!! nl2br(e($exam->instructions)) !!}
        </div>
    @endif

    <div class="questions">
        @foreach($exam->questions as $index => $question)
            <div class="question">
                <div class="question-header">
                    <span class="question-number">{{ $index + 1 }}.</span>
                    <div class="question-content">
                        @if($exam->type->value === 'entrance' && $question->topic?->subject)
                            <span style="font-size: 9px; text-transform: uppercase; font-weight: 800; color: #718096; display: block; margin-bottom: 2px;">
                                [{{ $question->topic->subject->name }}]
                            </span>
                        @endif
                        {!! nl2br(e($question->content)) !!}
                    </div>
                </div>
                <div class="options">
                    @php $labels = ['A', 'B', 'C', 'D', 'E', 'F']; @endphp
                    @foreach($question->options as $optIndex => $option)
                        <div class="option">
                            <span class="option-label">({{ $labels[$optIndex] ?? ($optIndex + 1) }})</span>
                            <span>{{ $option->content }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 50px; text-align: center; font-size: 11px; color: #4a5568; border-top: 1px solid #edf2f7; padding-top: 20px;">
        Examination generated via <strong>{{ config('app.name') }}</strong> | {{ date('d M, Y') }}
    </div>
</body>
</html>