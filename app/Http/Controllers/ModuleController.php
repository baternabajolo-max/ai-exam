<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMathPdfJob;
use App\Models\Achievement;
use App\Models\Course;
use App\Models\Module;
use App\Models\Result;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Request $request): View
    {
        $faculty = $request->user();
        $departmentId = $faculty->department_id;
        $courses = Course::query()
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();
        $selectedCourseId = $request->integer('course_id') ?: ($courses->count() === 1 ? $courses->first()->id : null);

        if ($selectedCourseId !== null && ! $courses->contains('id', $selectedCourseId)) {
            abort(404);
        }

        $modules = Module::query()
            ->with('course:id,name')
            ->withCount('subtopics')
            ->forFaculty($faculty)
            ->when($selectedCourseId !== null, fn ($query) => $query->where('course_id', $selectedCourseId))
            ->latest()
            ->get();

        return view('admin.modules.index', compact('modules', 'courses', 'selectedCourseId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Validate image upload up to 5MB
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);
        $course = Course::query()
            ->whereKey($validated['course_id'])
            ->where('department_id', $admin->department_id)
            ->firstOrFail();

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('modules/logos', 'public');
        }

        Module::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'logo_path' => $logoPath,
            'department_id' => $admin->department_id,
            'course_id' => $course->id,
        ]);

        return redirect()
            ->route('admin.modules.index', ['course_id' => $course->id])
            ->with('success', 'Module created successfully.');
    }

    public function show(Module $module): View
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        $module->load('subtopics');

        return view('admin.modules.show', compact('module'));
    }

    public function destroy(Module $module)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        // 1. Delete the module's logo file if it exists
        if ($module->logo_path && Storage::disk('public')->exists($module->logo_path)) {
            Storage::disk('public')->delete($module->logo_path);
        }

        // 2. Loop through all subtopics and delete their physical files
        foreach ($module->subtopics as $subtopic) {
            if ($subtopic->file_path && Storage::disk('public')->exists($subtopic->file_path)) {
                Storage::disk('public')->delete($subtopic->file_path);
            }
        }

        // 3. Delete the subtopic records from the database
        $module->subtopics()->delete();

        // 4. Finally, delete the module itself
        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', 'Module and all its subtopics were deleted successfully.');
    }

    public function storeSubtopic(Request $request, Module $module)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($module);

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:76800',
        ]);

        $filePath = $request->file('file')->store('modules/subtopics', 'public');

        $subtopic = $module->subtopics()->create([
            'title' => $request->title,
            'file_path' => $filePath,
            'text_extraction_status' => $this->isPdf($filePath)
                ? Subtopic::TEXT_EXTRACTION_PENDING
                : Subtopic::TEXT_EXTRACTION_NOT_REQUIRED,
        ]);

        if ($this->isPdf($filePath)) {
            ProcessMathPdfJob::dispatch($subtopic->id, $filePath);
        }

        return redirect()->route('admin.modules.show', $module)->with('success', 'Subtopic added successfully.');
    }

    public function destroySubtopic(Subtopic $subtopic)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($subtopic->module);

        if ($subtopic->file_path && Storage::disk('public')->exists($subtopic->file_path)) {
            Storage::disk('public')->delete($subtopic->file_path);
        }

        DB::transaction(function () use ($subtopic): void {
            $tests = Test::query()
                ->where('course_id', $subtopic->module->course_id)
                ->where(function ($query) use ($subtopic) {
                    $query->where('topic', 'like', '%'.$subtopic->title.'%')
                        ->orWhere('title', 'like', '%'.$subtopic->title.'%');
                })
                ->get();

            $affectedUserIds = [];
            foreach ($tests as $test) {
                $affectedUserIds = array_merge($affectedUserIds, Result::where('test_id', $test->id)->pluck('user_id')->all());
                $test->delete();
            }

            $subtopic->delete();

            $this->recalculateStudentPoints(
                array_unique($affectedUserIds),
                $subtopic->module->department_id,
                (int) $subtopic->module->course_id,
            );
        });

        return redirect()->back()->with('success', 'Subtopic deleted successfully.');
    }

    /**
     * Update the specified subtopic and handle optional file replacement.
     */
    public function updateSubtopic(Request $request, Subtopic $subtopic)
    {
        $this->ensureModuleBelongsToAuthenticatedAdmin($subtopic->module);

        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,zip|max:76800',
        ]);

        $subtopic->title = $request->title;
        $oldFilePath = $subtopic->file_path;
        $newFilePath = null;

        // If a new file is uploaded, replace the old one
        if ($request->hasFile('file')) {
            $newFilePath = $request->file('file')->store('modules/subtopics', 'public');
            $subtopic->file_path = $newFilePath;
            $subtopic->extracted_text = null;
            $subtopic->text_extraction_status = $this->isPdf($newFilePath)
                ? Subtopic::TEXT_EXTRACTION_PENDING
                : Subtopic::TEXT_EXTRACTION_NOT_REQUIRED;
        }

        $subtopic->save();

        if ($newFilePath !== null) {
            if ($oldFilePath && Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }

            if ($this->isPdf($newFilePath)) {
                ProcessMathPdfJob::dispatch($subtopic->id, $newFilePath);
            }
        }

        return redirect()->back()->with('success', 'Subtopic updated successfully.');
    }

    private function isPdf(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function ensureModuleBelongsToAuthenticatedAdmin(Module $module): void
    {
        abort_unless(
            auth()->user()->role === 'admin'
                && $module->department_id === auth()->user()->department_id,
            404,
        );
    }

    /**
     * Rebuild point totals from the remaining quiz results so deleted content
     * cannot leave stale leaderboard or achievement totals behind.
     *
     * @param  array<int, int>  $userIds
     */
    private function recalculateStudentPoints(array $userIds, int $departmentId, int $courseId): void
    {
        if ($userIds === []) {
            return;
        }

        $subtopics = Subtopic::whereHas('module', fn ($query) => $query
            ->where('department_id', $departmentId)
            ->where('course_id', $courseId))
            ->get();

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            $totalPoints = 0;

            foreach ($subtopics as $subtopic) {
                $totalPoints += $subtopic->earnedPointsForUser($user->id);
            }

            $user->update(['total_points' => $totalPoints]);
            $user->achievements()
                ->where('required_points', '>', $totalPoints)
                ->detach();
        }
    }
}
