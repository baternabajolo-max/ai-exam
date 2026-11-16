<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::with('courses')->withCount(['users', 'tests'])->get();

        return view('super-admin.departments.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => Str::upper(trim((string) $request->input('code'))) ?: null,
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('departments', 'code')],
        ]);

        Department::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? $this->generateCollegeCode($validated['name']),
        ]);

        return redirect()->back()->with('success', 'College created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);

        $department->update([
            'name' => $validated['name'],
        ]);

        return redirect()->back()->with('success', 'College name updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('super-admin.departments.index')->with('success', 'College deleted successfully.');
    }

    public function storeCourse(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($department, $validated): void {
            $course = $department->courses()->create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
            ]);

            $firstCourseId = $department->courses()
                ->orderBy('id')
                ->value('id');

            $department->modules()
                ->whereNull('course_id')
                ->update(['course_id' => $firstCourseId]);
        });

        return back()->with('success', 'Course added to '.$department->name);
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $course->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Course updated successfully.');
    }

    public function deleteCourse(Course $course): RedirectResponse
    {
        if ($course->modules()->exists()) {
            return back()->withErrors([
                'course' => 'This course still has modules assigned. Reassign or delete those modules before removing the course.',
            ]);
        }

        $course->delete();

        return back()->with('success', 'Course removed.');
    }

    private function generateCollegeCode(string $collegeName): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^a-zA-Z0-9]/', '', $collegeName) ?: 'COL', 0, 3));
        $code = $prefix;
        $suffix = 1;

        while (Department::query()->where('code', $code)->exists()) {
            $code = $prefix.$suffix;
            $suffix++;
        }

        return $code;
    }
}
