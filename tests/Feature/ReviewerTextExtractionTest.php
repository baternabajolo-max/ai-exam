<?php

namespace Tests\Feature;

use App\Jobs\ExtractSubtopicText;
use App\Jobs\ProcessMathPdfJob;
use App\Models\Course;
use App\Models\Department;
use App\Models\Module;
use App\Models\Subtopic;
use App\Models\Test;
use App\Models\User;
use App\Services\AiService;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ReviewerTextExtractionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_upload_queues_pdf_extraction_and_marks_reviewer_pending(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Software Design', 'department_id' => $department->id, 'course_id' => $course->id]);

        $response = $this->actingAs($admin)->post(route('admin.modules.subtopics.store', $module), [
            'title' => 'Large Reviewer',
            'file' => UploadedFile::fake()->create('reviewer.pdf', 1024, 'application/pdf'),
        ]);

        $response->assertRedirect(route('admin.modules.show', $module));
        $subtopic = Subtopic::where('title', 'Large Reviewer')->sole();
        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
            'extracted_text' => null,
        ]);
        Storage::disk('public')->assertExists($subtopic->file_path);
        Queue::assertPushed(ProcessMathPdfJob::class, fn (ProcessMathPdfJob $job): bool => $job->subtopicId === $subtopic->id
            && $job->filePath === $subtopic->file_path
            && $job->connection === 'database_math'
            && $job->queue === 'math-pdf');
    }

    public function test_student_review_shows_extraction_status_without_showing_pdf_until_text_is_ready(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/legacy-reviewer.pdf',
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $response = $this->actingAs($student)->get(route('student.learn.review', $subtopic));

        $response
            ->assertSee('Queued for math processing...')
            ->assertSee('This page will update automatically when processing is complete.')
            ->assertSee('math-processing-spinner', false)
            ->assertSee('Checking again shortly...')
            ->assertSee('const pollInterval = 5000', false)
            ->assertSee('data-status-url="'.route('student.learn.subtopic.extraction-status', $subtopic).'"', false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('PDF reviewer')
            ->assertDontSee('Open or download original reviewer');
        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Queue::assertPushed(ProcessMathPdfJob::class);
    }

    public function test_student_review_shows_the_math_processing_card_while_a_pdf_is_processing(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/processing-reviewer.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PROCESSING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertSee('Processing math equations...')
            ->assertSee('math-processing-spinner', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('const pollInterval = 5000', false);

        Queue::assertNothingPushed();
    }

    public function test_extraction_job_stores_text_and_student_review_uses_the_cached_result(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/cached-reviewer.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $extractor = $this->mock(PdfTextExtractor::class);
        $extractor->shouldReceive('extract')
            ->once()
            ->with(Storage::disk('public')->path($subtopic->file_path))
            ->andReturn('Stored extracted reviewer text with $$E = mc^2$$. <script>alert("unsafe")</script>');

        (new ProcessMathPdfJob($subtopic->id, $subtopic->file_path))->handle($extractor);

        $extractedText = 'Stored extracted reviewer text with $$E = mc^2$$. <script>alert("unsafe")</script>';
        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'extracted_text' => $extractedText,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
        ]);
        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertSee('Stored extracted reviewer text with $$E = mc^2$$.')
            ->assertSee('&lt;script&gt;alert(', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertSee('id="documentBody"', false)
            ->assertSee('katex@0.16.22/dist/katex.min.css', false)
            ->assertSee('renderMathInElement(documentBody', false)
            ->assertSee('new MutationObserver', false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('PDF reviewer');
        Queue::assertNothingPushed();
    }

    public function test_legacy_queued_extraction_job_still_stores_math_markdown(): void
    {
        Storage::fake('public');
        [, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/legacy-queued-reviewer.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $extractor = $this->mock(PdfTextExtractor::class);
        $extractor->shouldReceive('extract')
            ->once()
            ->with(Storage::disk('public')->path($subtopic->file_path))
            ->andReturn('Legacy job output with $$x^2$$.');

        (new ExtractSubtopicText($subtopic->id, $subtopic->file_path))->handle($extractor);

        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'extracted_text' => 'Legacy job output with $$x^2$$.',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
        ]);
    }

    public function test_student_cannot_read_extraction_status_for_another_department(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        [$student] = $this->createStudentSubtopic();

        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherModule = Module::create(['title' => 'Teaching', 'department_id' => $otherDepartment->id]);
        $foreignSubtopic = Subtopic::create([
            'module_id' => $otherModule->id,
            'title' => 'Foreign reviewer',
        ]);

        $this->actingAs($student)
            ->getJson(route('student.learn.subtopic.extraction-status', $foreignSubtopic))
            ->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_student_can_check_the_status_of_a_queued_pdf_extraction(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/status-reviewer.pdf',
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $this->actingAs($student)
            ->getJson(route('student.learn.subtopic.extraction-status', $subtopic))
            ->assertOk()
            ->assertJsonPath('status', Subtopic::TEXT_EXTRACTION_PENDING);

        Queue::assertPushed(ProcessMathPdfJob::class);
    }

    public function test_replacing_a_pdf_clears_old_text_and_queues_the_new_file(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $module = Module::create(['title' => 'Software Design', 'department_id' => $department->id, 'course_id' => $course->id]);
        $subtopic = Subtopic::create([
            'module_id' => $module->id,
            'title' => 'Existing Reviewer',
            'file_path' => 'modules/subtopics/old-reviewer.pdf',
            'extracted_text' => 'Old extracted text.',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $this->actingAs($admin)
            ->put(route('admin.subtopics.update', $subtopic), [
                'title' => 'Existing Reviewer',
                'file' => UploadedFile::fake()->create('replacement.pdf', 1024, 'application/pdf'),
            ])
            ->assertRedirect();

        $subtopic->refresh();
        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'extracted_text' => null,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->assertMissing('modules/subtopics/old-reviewer.pdf');
        Storage::disk('public')->assertExists($subtopic->file_path);
        Queue::assertPushed(ProcessMathPdfJob::class, fn (ProcessMathPdfJob $job): bool => $job->subtopicId === $subtopic->id
            && $job->filePath === $subtopic->file_path);
    }

    public function test_failed_extraction_is_visible_to_the_student(): void
    {
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/failed-reviewer.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PROCESSING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        (new ProcessMathPdfJob($subtopic->id, $subtopic->file_path))
            ->failed(new RuntimeException('PyMuPDF is unavailable.'));

        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_FAILED,
        ]);
        $this->actingAs($student)
            ->get(route('student.learn.review', $subtopic))
            ->assertSee('The reviewer text could not be prepared. Please contact your faculty member.')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('Open or download original reviewer');
    }

    public function test_a_stale_extraction_job_cannot_overwrite_a_replacement_reviewer(): void
    {
        Storage::fake('public');
        [, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/old-reviewer.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $extractor = $this->mock(PdfTextExtractor::class);
        $extractor->shouldReceive('extract')
            ->once()
            ->with(Storage::disk('public')->path($subtopic->file_path))
            ->andReturnUsing(function (string $path) use ($subtopic): string {
                Subtopic::query()->whereKey($subtopic->id)->update([
                    'file_path' => 'modules/subtopics/replacement.pdf',
                    'extracted_text' => null,
                    'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
                ]);

                return 'Text from the replaced reviewer.';
            });

        (new ProcessMathPdfJob($subtopic->id, $subtopic->file_path))->handle($extractor);

        $this->assertDatabaseHas('subtopics', [
            'id' => $subtopic->id,
            'file_path' => 'modules/subtopics/replacement.pdf',
            'extracted_text' => null,
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
    }

    public function test_subtopic_quiz_uses_stored_pdf_text_instead_of_parsing_during_the_request(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/quiz-reviewer.pdf',
            'extracted_text' => 'Cached concept for quiz questions.',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_COMPLETED,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $aiService = $this->mock(AiService::class);
        $aiService->shouldReceive('generateSubtopicQuiz')
            ->once()
            ->withArgs(fn (string $title, string $reviewerText, int $count, string $questionType, array $previousQuestions): bool => $title === $subtopic->title
                && str_contains($reviewerText, 'Cached concept for quiz questions.')
                && $count === 10)
            ->andReturn([]);

        $this->actingAs($student)
            ->get(route('student.learn.quiz', $subtopic))
            ->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_subtopic_quiz_waits_for_pending_pdf_extraction(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/pending-quiz.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');

        $this->from(route('student.learn.review', $subtopic))
            ->actingAs($student)
            ->get(route('student.learn.quiz', $subtopic))
            ->assertRedirect(route('student.learn.review', $subtopic))
            ->assertSessionHasErrors('quiz');

        Queue::assertPushed(ProcessMathPdfJob::class);
    }

    public function test_general_test_queues_pending_subtopic_pdfs_without_parsing_in_the_request(): void
    {
        Queue::fake([ProcessMathPdfJob::class]);
        Storage::fake('public');
        [$student, $subtopic] = $this->createStudentSubtopic([
            'file_path' => 'modules/subtopics/general-test.pdf',
            'text_extraction_status' => Subtopic::TEXT_EXTRACTION_PENDING,
        ]);
        Storage::disk('public')->put($subtopic->file_path, '%PDF-1.7');
        $test = Test::create([
            'title' => 'General Assessment',
            'department_id' => $student->department_id,
            'is_general' => true,
        ]);

        $this->actingAs($student)
            ->getJson(route('student.tests.build', $test))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Reviewer text is being prepared. Please try generating the test again in a moment.');

        Queue::assertPushed(ProcessMathPdfJob::class);
    }

    /**
     * @param  array<string, mixed>  $subtopicAttributes
     * @return array{0: User, 1: Subtopic}
     */
    private function createStudentSubtopic(array $subtopicAttributes = []): array
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $module = Module::create(['title' => 'Science', 'department_id' => $department->id]);
        $subtopic = Subtopic::create(array_merge([
            'module_id' => $module->id,
            'title' => 'Photosynthesis',
        ], $subtopicAttributes));

        return [$student, $subtopic];
    }
}
