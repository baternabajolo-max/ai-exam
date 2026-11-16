<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LEMU Flashcard Printing</title>
    <style>
        :root { font-family: Inter, Arial, sans-serif; color: #0f172a; }
        body { background: #f8fafc; margin: 0; padding: 2rem; }
        .toolbar { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; margin: 0 auto 2rem; max-width: 1100px; padding: 1.5rem; }
        .toolbar h1 { margin: 0 0 .5rem; }
        .toolbar p { color: #64748b; margin: 0 0 1rem; }
        .selection { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .selection label { align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .6rem; display: flex; gap: .6rem; padding: .7rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
        button { background: #2563eb; border: 0; border-radius: .5rem; color: #fff; cursor: pointer; font-weight: 600; padding: .7rem 1rem; }
        button.secondary { background: #475569; }
        .sheet { display: grid; gap: .3in .25in; grid-template-columns: repeat(3, 2in); margin: 0 auto; width: 6.5in; }
        .back-sheet { direction: rtl; }
        .back-sheet .card { direction: ltr; }
        .card { align-items: center; border: 1px solid #334155; display: flex; height: 1in; justify-content: center; overflow: hidden; padding: .12in; text-align: center; }
        .card p { font-size: 15pt; margin: 0; overflow-wrap: anywhere; }
        .blue { background: #bfdbfe; }.green { background: #bbf7d0; }.red { background: #fecaca; }.yellow { background: #fef08a; }.pink { background: #fbcfe8; }
        .empty { color: #64748b; padding: 2rem; text-align: center; }
        @page { size: 8.5in 13in; margin: .5in; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { margin: 0; }
            .card { -webkit-print-color-adjust: exact; print-color-adjust: exact; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <section class="toolbar">
        <h1>Print flashcards</h1>
        <p>Select cards, then choose which side to print first. Reinsert the paper without changing its orientation before printing the other side.</p>
        <label><input type="checkbox" id="select-all"> Select all cards</label>
        <div class="selection">
            @foreach($allFlashcards as $flashcard)
                <label>
                    <input type="checkbox" class="card-choice" value="{{ $flashcard->id }}" @checked($flashcards->contains('id', $flashcard->id))>
                    <span>{{ \Illuminate\Support\Str::limit($flashcard->front, 55) }}</span>
                </label>
            @endforeach
        </div>
        <div class="actions">
            <button type="button" data-side="front">Print front first</button>
            <button type="button" class="secondary" data-side="back">Print back first</button>
        </div>
        <p class="empty" id="print-help">Choose cards and a side to generate the print sheet.</p>
    </section>
    @if($side && $flashcards->isNotEmpty())
        <section class="sheet {{ $side === 'back' ? 'back-sheet' : '' }}">
            @foreach($flashcards as $flashcard)
                <div class="card {{ $flashcard->color }}">
                    <p>{{ $side === 'front' ? $flashcard->front : ($flashcard->back ?: 'No definition yet.') }}</p>
                </div>
            @endforeach
        </section>
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
    <script>
        const choices = [...document.querySelectorAll('.card-choice')];
        document.getElementById('select-all').addEventListener('change', (event) => {
            choices.forEach((choice) => choice.checked = event.target.checked);
        });
        document.querySelectorAll('[data-side]').forEach((button) => {
            button.addEventListener('click', () => {
                const selected = choices.filter((choice) => choice.checked).map((choice) => choice.value);
                if (!selected.length) {
                    document.getElementById('print-help').textContent = 'Select at least one flashcard first.';
                    return;
                }
                const params = new URLSearchParams({ cards: selected.join(','), side: button.dataset.side });
                window.location.href = `{{ route('student.flashcards.print') }}?${params.toString()}`;
            });
        });
    </script>
</body>
</html>
