<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $result->test?->title ?? 'Test' }}</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.35; margin: 0; }
        .print-toolbar { margin: 0 auto 24px; max-width: 760px; }
        .print-toolbar button { cursor: pointer; padding: 8px 14px; }
        .paper { margin: 0 auto; max-width: 760px; }
        .header { border-bottom: 1px solid #111; margin-bottom: 20px; padding-bottom: 10px; text-align: center; }
        .header h1 { font-size: 16pt; margin: 0 0 4px; }
        .header p { margin: 0; }
        .question { break-inside: avoid; margin-bottom: 18px; }
        .question-text { font-weight: 700; margin-bottom: 6px; }
        .options { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 28px; padding-left: 22px; }
        .option { min-height: 18px; }
        .result-summary { border-top: 1px solid #111; margin-top: 24px; padding-top: 10px; }
        .answers { border-collapse: collapse; margin-top: 12px; width: 100%; }
        .answers th, .answers td { border: 1px solid #777; padding: 5px 7px; text-align: left; }
        @media print {
            .print-toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <main class="paper">
        <header class="header">
            <h1>{{ $result->test?->title ?? 'Test' }}</h1>
            <p>{{ $result->test?->is_general ? 'General Test' : 'Quiz' }}</p>
            <p>{{ $result->created_at?->format('F d, Y') }}</p>
        </header>

        @foreach($questionsData as $index => $data)
            @php
                $question = $data['question_text'] ?? $data['question'] ?? 'Question';
                $options = is_array($data['options'] ?? null) ? $data['options'] : [];
            @endphp
            <section class="question">
                <div class="question-text">{{ $index + 1 }}. {{ $question }}</div>
                <div class="options">
                    @foreach($options as $optionIndex => $option)
                        <div class="option">{{ chr(65 + $optionIndex) }}. {{ $option }}</div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="result-summary">
            <strong>Result:</strong> {{ $result->score }} / {{ $result->total_questions }}
            ({{ number_format($result->percentage, 1) }}%)

            <table class="answers">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Student Answer</th>
                        <th>Correct Answer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questionsData as $index => $data)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $data['user_answer'] ?? 'No answer' }}</td>
                            <td>{{ $data['correct_answer'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
