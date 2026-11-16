<?php

namespace App\Http\Controllers;

use App\Models\Flashcard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlashcardController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $flashcards = Flashcard::whereBelongsTo($request->user())
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('front', 'like', "%{$search}%")->orWhere('back', 'like', "%{$search}%")))
            ->latest()
            ->paginate(18);

        return view('student.flashcards.index', compact('flashcards', 'search'));
    }

    public function print(Request $request): View
    {
        $selectedIds = collect(explode(',', $request->string('cards')->toString()))
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id);
        $side = $request->string('side')->toString();
        $side = in_array($side, ['front', 'back'], true) ? $side : null;
        $allFlashcards = $request->user()->flashcards()->latest()->get();
        $flashcards = $selectedIds->isEmpty()
            ? collect()
            : $allFlashcards->whereIn('id', $selectedIds->all())->values();

        return view('student.flashcards.print', compact('allFlashcards', 'flashcards', 'side'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'front' => ['required', 'string', 'max:1000'],
            'back' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'in:blue,green,red,yellow,pink'],
            'subtopic_id' => ['nullable', 'integer', 'exists:subtopics,id'],
            'existing_flashcard_id' => ['nullable', 'integer', 'exists:flashcards,id'],
        ]);

        $existingId = $data['existing_flashcard_id'] ?? null;
        unset($data['existing_flashcard_id']);

        if ($existingId) {
            $flashcard = $request->user()->flashcards()->findOrFail($existingId);
            $flashcard->update($data);
        } else {
            $request->user()->flashcards()->create($data);
        }

        return back()->with('success', $existingId ? 'Flashcard updated.' : 'Flashcard saved.');
    }

    public function update(Request $request, Flashcard $flashcard): RedirectResponse
    {
        abort_unless($flashcard->user_id === $request->user()->id, 404);

        $flashcard->update($request->validate([
            'front' => ['required', 'string', 'max:1000'],
            'back' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', 'in:blue,green,red,yellow,pink'],
        ]));

        return back()->with('success', 'Flashcard updated.');
    }

    public function destroy(Request $request, Flashcard $flashcard): RedirectResponse
    {
        abort_unless($flashcard->user_id === $request->user()->id, 404);
        $flashcard->delete();

        return back()->with('success', 'Flashcard deleted.');
    }
}
