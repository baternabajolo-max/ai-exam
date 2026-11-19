<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $test->title }} | LEMU Examination Paper</title>
    <style>
        :root {
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
        }

        body {
            margin: 0;
            background: #e5e7eb;
        }

        .paper {
            box-sizing: border-box;
            width: min(8.5in, 100%);
            min-height: 11in;
            margin: 1.25rem auto;
            padding: .55in .65in;
            background: #fff;
        }

        .paper-header {
            display: grid;
            grid-template-columns: 1in 1fr;
            align-items: center;
            gap: .2in;
            text-align: center;
        }

        .paper-logo {
            width: .85in;
            height: .85in;
            object-fit: contain;
        }

        .paper-header h1,
        .paper-header h2,
        .paper-header p {
            margin: .04in 0;
        }

        .paper-header h1 {
            font-size: 15pt;
            letter-spacing: .04em;
        }

        .paper-header h2 {
            font-size: 12pt;
        }

        .paper-header p {
            font-size: 10pt;
        }

        .student-fields {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: .25in;
            margin: .35in 0 .2in;
        }

        .field-line {
            display: inline-block;
            min-height: .23in;
            width: calc(100% - .55in);
            border-bottom: 1px solid #111;
            vertical-align: bottom;
        }

        .instructions {
            margin-bottom: .2in;
            font-weight: 700;
        }

        .question {
            margin: 0 0 .16in;
            break-inside: avoid;
        }

        .question-text {
            margin-bottom: .04in;
            font-weight: 600;
        }

        .question-image {
            display: block;
            max-height: 3in;
            max-width: 100%;
            object-fit: contain;
            object-position: left;
        }

        .question-number {
            margin-right: .06in;
        }

        .options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .02in .2in;
            margin-left: .25in;
        }

        .option-letter {
            display: inline-block;
            min-width: .22in;
        }

        .toolbar {
            position: sticky;
            top: 0;
            padding: .65rem;
            background: #111827;
            text-align: center;
        }

        .toolbar button {
            padding: .5rem 1rem;
            border: 0;
            border-radius: .4rem;
            background: #2563eb;
            color: #fff;
            font: inherit;
            cursor: pointer;
        }

        @page {
            size: letter portrait;
            margin: .45in;
        }

        @media print {
            body {
                background: #fff;
            }

            .paper {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print examination</button>
    </div>

    <main class="paper">
        <header class="paper-header">
            <img class="paper-logo" src="{{ asset('storage/images/systemlogo.jpg') }}" alt="LEMU system logo">
            <div>
                <h1>LEMU</h1>
                <p>{{ $test->course?->department?->name ?? $test->department?->name ?? 'College' }}</p>
                <p>{{ $test->course?->name ?? 'Program' }}</p>
                <h2>{{ $test->title }}</h2>
            </div>
        </header>

        <div class="student-fields" aria-label="Student information">
            <div>Name: <span class="field-line"></span></div>
            <div>Date: <span class="field-line"></span></div>
        </div>

        @if ($test->topic)
            <p><strong>Subject:</strong> {{ $test->topic }}</p>
        @endif
        <p class="instructions">Instruction: Encircle the letter of the correct answer.</p>

        @foreach ($test->questions as $question)
            <section class="question">
                <div class="question-text">
                    <span class="question-number">{{ $loop->iteration }}.</span>{{ $question->question }}
                </div>
                @if ($question->image_path)
                    <img class="question-image" src="{{ asset('storage/'.$question->image_path) }}" alt="Question illustration">
                @endif
                <div class="options">
                    @foreach ($question->options ?? [] as $option)
                        <div><span class="option-letter">{{ chr(65 + $loop->index) }}.</span>{{ $option }}</div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </main>
</body>
</html>
