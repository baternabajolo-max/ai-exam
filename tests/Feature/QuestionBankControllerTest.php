<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\Result;
use App\Models\Test as Exam;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\QuestionBankItemDisabledNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionBankControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_question_bank_uses_the_program_dropdown_without_a_duplicate_choose_program_button(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        Course::create(['name' => 'Information Systems', 'code' => 'IS', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.index'))
            ->assertOk()
            ->assertSee('id="faculty-question-course"', false)
            ->assertSee('id="faculty-question-faculty"', false)
            ->assertSee('Filter by program')
            ->assertDontSee('data-bs-target="#createTopicModal"', false)
            ->assertDontSee('data-focus-program', false)
            ->assertDontSee('> Choose a program</button>', false)
            ->assertViewHas('selectedTopic', null);

        $this->get(route('admin.question-bank.index', ['course_id' => '']))
            ->assertSee('All my programs')
            ->assertDontSee('data-bs-target="#createTopicModal"', false);

        $this->get(route('admin.question-bank.index', ['course_id' => Course::query()->firstOrFail()->id]))
            ->assertSee('data-bs-target="#createTopicModal"', false)
            ->assertDontSee('data-bs-target="#createQuestionModal"', false)
            ->assertSee('Topic folders')
            ->assertDontSee('Choose a topic')
            ->assertDontSee('Question collection');
    }

    public function test_faculty_can_create_a_trimmed_topic_for_a_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);

        $response = $this->actingAs($faculty)
            ->post(route('admin.question-bank.topics.store'), [
                'course_id' => $course->id,
                'name' => '  Network Security  ',
                'form_context' => 'topic',
            ]);

        $topic = Topic::query()->firstOrFail();
        $response->assertRedirect(route('admin.question-bank.index', ['course_id' => $course->id]));
        $this->assertSame('Network Security', $topic->name);
        $this->assertSame($faculty->id, $topic->created_by);
        $this->get(route('admin.question-bank.index', ['course_id' => $course->id]))
            ->assertSee('Topic created. You can now add questions.')
            ->assertSee('toast-success')
            ->assertDontSee('alert alert-success', false);
        $this->assertDatabaseHas('topics', [
            'course_id' => $course->id,
            'name' => 'Network Security',
            'name_key' => 'network security',
        ]);
    }

    public function test_topic_names_are_case_insensitively_unique_within_a_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network Security');
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);

        $this->followingRedirects()
            ->actingAs($faculty)
            ->from(route('admin.question-bank.index', ['course_id' => $course->id]))
            ->post(route('admin.question-bank.topics.store'), [
                'course_id' => $course->id,
                'name' => '  nETWORK sECURITY  ',
                'form_context' => 'topic',
            ])
            ->assertOk()
            ->assertSee('A topic with this name already exists in this program.')
            ->assertSee('id="createTopicModal"', false);
        $this->assertDatabaseCount('topics', 1);

        $this->post(route('admin.question-bank.topics.store'), [
            'course_id' => $otherCourse->id,
            'name' => $topic->name,
            'form_context' => 'topic',
        ])->assertNotFound();
        $this->assertDatabaseCount('topics', 1);
    }

    public function test_faculty_cannot_rename_a_topic_folder_to_an_existing_name(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $firstTopic = $this->createTopic($course, $faculty, 'Network Security');
        $secondTopic = $this->createTopic($course, $faculty, 'Databases');

        $this->followingRedirects()
            ->actingAs($faculty)
            ->from(route('admin.question-bank.topics.show', $secondTopic))
            ->put(route('admin.question-bank.topics.update', $secondTopic), [
                'name' => '  network SECURITY ',
                'form_context' => 'topic-edit-'.$secondTopic->id,
            ])
            ->assertOk()
            ->assertSee('A topic with this name already exists in this program.')
            ->assertSee('id="editTopicModal'.$secondTopic->id.'"', false);

        $this->assertDatabaseHas('topics', [
            'id' => $firstTopic->id,
            'name' => 'Network Security',
        ]);
        $this->assertDatabaseHas('topics', [
            'id' => $secondTopic->id,
            'name' => 'Databases',
        ]);
    }

    public function test_snapshot_only_topics_are_hidden_and_can_be_created_as_real_folders(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $historicalTopic = Topic::create([
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_folder' => false,
            'name' => 'Adv Engg Math 2ed Greenberg',
        ]);
        $exam = Exam::create([
            'title' => 'Historical Mathematics Exam',
            'topic' => $historicalTopic->name,
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);
        $snapshot = Question::create([
            'test_id' => $exam->id,
            'topic_id' => $historicalTopic->id,
            'question' => 'Historical question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => $historicalTopic->name,
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.index', ['course_id' => $course->id]))
            ->assertDontSee($historicalTopic->name);
        $this->get(route('admin.question-bank.topics.show', $historicalTopic))
            ->assertNotFound();

        $this->post(route('admin.question-bank.topics.store'), [
            'course_id' => $course->id,
            'name' => $historicalTopic->name,
            'form_context' => 'topic',
        ])->assertRedirect(route('admin.question-bank.index', ['course_id' => $course->id]));

        $folder = Topic::query()->where('is_folder', true)->firstOrFail();
        $this->assertSame($historicalTopic->name, $folder->name);
        $this->assertDatabaseHas('questions', [
            'id' => $snapshot->id,
            'topic_id' => $historicalTopic->id,
            'question' => 'Historical question?',
        ]);
        $this->get(route('admin.question-bank.index', ['course_id' => $course->id]))
            ->assertSee($folder->name)
            ->assertSee(route('admin.question-bank.topics.show', $folder), false);
    }

    public function test_faculty_question_list_only_contains_questions_from_the_selected_topic(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $networkTopic = $this->createTopic($course, $faculty, 'Network Security');
        $databaseTopic = $this->createTopic($course, $faculty, 'Databases');
        $networkQuestion = $this->createQuestionBankItem($course, $faculty, $networkTopic, 'Network question?');
        $databaseQuestion = $this->createQuestionBankItem($course, $faculty, $databaseTopic, 'Database question?');

        $response = $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', $networkTopic))
            ->assertSee('data-bs-target="#createQuestionModal"', false)
            ->assertSee('Network Security')
            ->assertSee('Back to Topics')
            ->assertSee($networkQuestion->question)
            ->assertDontSee($databaseQuestion->question)
            ->assertSee('1 total');

        $this->assertSame($networkTopic->id, $response->viewData('selectedTopic')->id);
        $this->assertSame([$networkQuestion->id], $response->viewData('items')->getCollection()->modelKeys());
    }

    public function test_faculty_can_filter_folder_questions_by_difficulty(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network Security');
        $easyQuestion = $this->createQuestionBankItem($course, $faculty, $topic, 'Easy question?');
        $mediumQuestion = $this->createQuestionBankItem($course, $faculty, $topic, 'Medium question?');
        $moderateQuestion = $this->createQuestionBankItem($course, $faculty, $topic, 'Moderate question?');
        $hardQuestion = $this->createQuestionBankItem($course, $faculty, $topic, 'Hard question?');
        $easyQuestion->update(['difficulty' => 'easy']);
        $mediumQuestion->update(['difficulty' => 'medium']);
        $moderateQuestion->update(['difficulty' => 'moderate']);
        $hardQuestion->update(['difficulty' => 'hard']);

        $easyResponse = $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', ['topic' => $topic->id, 'difficulty' => 'easy']))
            ->assertOk()
            ->assertSee('Easy question?')
            ->assertDontSee('Medium question?');
        $this->assertSame('easy', $easyResponse->viewData('selectedDifficulty'));
        $this->assertSame([$easyQuestion->id], $easyResponse->viewData('items')->getCollection()->modelKeys());

        $mediumResponse = $this->get(route('admin.question-bank.topics.show', ['topic' => $topic->id, 'difficulty' => 'medium']))
            ->assertOk()
            ->assertSee('Medium question?')
            ->assertSee('Moderate question?')
            ->assertDontSee('Easy question?')
            ->assertDontSee('Hard question?');
        $this->assertEqualsCanonicalizing(
            [$mediumQuestion->id, $moderateQuestion->id],
            $mediumResponse->viewData('items')->getCollection()->modelKeys(),
        );

        $hardResponse = $this->get(route('admin.question-bank.topics.show', ['topic' => $topic->id, 'difficulty' => 'hard']))
            ->assertOk()
            ->assertSee('Hard question?')
            ->assertDontSee('Easy question?');
        $this->assertSame([$hardQuestion->id], $hardResponse->viewData('items')->getCollection()->modelKeys());
    }

    public function test_topic_folders_open_a_question_page_and_can_be_edited_and_deleted(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network Security');
        $item = $this->createQuestionBankItem($course, $faculty, $topic, 'Which protocol secures web traffic?');
        $exam = Exam::create([
            'title' => 'Network Security Exam',
            'topic' => 'Network Security',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);
        $snapshot = Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $item->id,
            'topic_id' => $topic->id,
            'question' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'topic' => $item->topic,
        ]);

        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $otherDepartment->id]);
        $this->actingAs($otherFaculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertNotFound();
        $this->put(route('admin.question-bank.topics.update', $topic), ['name' => 'Unauthorized rename'])
            ->assertNotFound();
        $this->delete(route('admin.question-bank.topics.destroy', $topic))
            ->assertNotFound();

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.index', ['course_id' => $course->id]))
            ->assertSee(route('admin.question-bank.topics.show', $topic), false)
            ->assertSee('bi-folder2-open', false)
            ->assertDontSee('Edit '.$topic->name.' folder')
            ->assertDontSee('Delete '.$topic->name.' folder')
            ->assertDontSee('Question collection')
            ->assertDontSee('Choose a topic')
            ->assertDontSee($item->question);

        $this->get(route('admin.question-bank.topics.show', $topic))
            ->assertOk()
            ->assertSee('Back to Topics')
            ->assertSee($item->question)
            ->assertSee('Edit folder')
            ->assertSee('Delete folder')
            ->assertDontSee('id="topics-heading"', false);

        $this->get(route('admin.question-bank.index', [
            'course_id' => $course->id,
            'topic_id' => $topic->id,
        ]))->assertRedirect(route('admin.question-bank.topics.show', $topic));

        $this->put(route('admin.question-bank.topics.update', $topic), [
            'name' => '  Network Fundamentals  ',
            'form_context' => 'topic-edit-'.$topic->id,
        ])->assertRedirect(route('admin.question-bank.topics.show', $topic));

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'name' => 'Network Fundamentals',
            'name_key' => 'network fundamentals',
        ]);
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'topic' => 'Network Fundamentals',
        ]);

        $this->delete(route('admin.question-bank.topics.destroy', $topic))
            ->assertRedirect(route('admin.question-bank.index', ['course_id' => $course->id]));

        $this->assertDatabaseMissing('topics', ['id' => $topic->id]);
        $this->assertDatabaseMissing('question_bank_items', ['id' => $item->id]);
        $this->assertDatabaseHas('questions', [
            'id' => $snapshot->id,
            'topic_id' => null,
            'question' => 'Which protocol secures web traffic?',
            'topic' => 'Network Security',
        ]);
    }

    public function test_faculty_can_disable_and_reenable_a_question_for_future_exams(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network Security');
        $item = $this->createQuestionBankItem($course, $faculty, $topic, 'Question available for new exams?');

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertSee('Enabled for new exams')
            ->assertSee('Disable');

        $this->actingAs($otherFaculty)
            ->patch(route('admin.question-bank.status', $item), ['is_active' => '0'])
            ->assertNotFound();

        $this->actingAs($faculty)
            ->patch(route('admin.question-bank.status', $item), ['is_active' => '0'])
            ->assertRedirect(route('admin.question-bank.topics.show', $topic));
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'is_active' => false]);

        $this->get(route('admin.question-bank.topics.show', $topic))
            ->assertSee('Disabled for new exams')
            ->assertSee('Enable');

        $this->patch(route('admin.question-bank.status', $item), ['is_active' => '1'])
            ->assertRedirect(route('admin.question-bank.topics.show', $topic));
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'is_active' => true]);
    }

    public function test_faculty_question_bank_opens_create_and_edit_forms_from_modal_buttons(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'name' => 'test',
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $topic = $this->createTopic($course, $faculty, 'Network security');
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'What protects web traffic?',
            'options' => ['HTTPS', 'FTP', 'SMTP', 'HTTP'],
            'correct_answer' => 'HTTPS',
            'topic' => 'Network security',
            'explanation' => 'HTTPS protects web traffic using TLS.',
            'image_path' => 'question-bank-images/network-equation.png',
            'difficulty' => 'hard',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertDontSee('data-bs-target="#createTopicModal"', false)
            ->assertSee('data-bs-target="#createQuestionModal"', false)
            ->assertSee('Edit folder')
            ->assertSee('Disable')
            ->assertSee($topic->name)
            ->assertSee('Create a question')
            ->assertSee('id="createQuestionModal"', false)
            ->assertSee('Save Question')
            ->assertSee('question-bank-modal')
            ->assertSee('modal-footer px-4 py-3', false)
            ->assertSee('data-bs-target="#viewQuestionModal'.$item->id.'"', false)
            ->assertSee('id="viewQuestionModal'.$item->id.'"', false)
            ->assertSee('aria-label="View question and answer choices"', false)
            ->assertSee("document.querySelectorAll('.faculty-question-bank__question-grid .modal')", false)
            ->assertSee('HTTPS')
            ->assertSee('FTP')
            ->assertSee('Correct answer')
            ->assertSee('HTTPS protects web traffic using TLS.')
            ->assertSee('id="editQuestionModal'.$item->id.'"', false)
            ->assertSee('data-open-edit-modal="#editQuestionModal'.$item->id.'"', false)
            ->assertSee(route('admin.question-bank.destroy', $item), false)
            ->assertSee('name="form_context" value="create"', false)
            ->assertSee('name="form_context" value="edit-'.$item->id.'"', false)
            ->assertSee('name="question_image"', false)
            ->assertSee('name="difficulty"', false)
            ->assertSee('Easy')
            ->assertSee('Moderate')
            ->assertSee('Hard')
            ->assertSee('bi-funnel', false)
            ->assertSee('Filter questions by health status')
            ->assertSee('All Health')
            ->assertSee('Added by: test')
            ->assertDontSee('data-url="'.route('admin.question-bank.revision', $item).'"', false)
            ->assertDontSee('Module topic')
            ->assertDontSee('name="subtopic_id"', false)
            ->assertSee('name="correct_choice"', false)
            ->assertSee('accept=".jpg,.jpeg,.png,.webp"', false)
            ->assertSee('src="'.asset('storage/'.$item->image_path).'"', false)
            ->assertDontSee('View Item Analysis');
    }

    public function test_question_preview_aggregates_attempts_and_health_filter_covers_the_topic_roster(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network security');
        $item = $this->createQuestionBankItem($course, $faculty, $topic, 'Which choice is correct?');
        $item->update([
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'difficulty' => 'easy',
        ]);
        $untestedItem = $this->createQuestionBankItem($course, $faculty, $topic, 'Which question has no responses?');
        $untestedItem->update(['author_id' => null]);
        $healthyItem = $this->createQuestionBankItem($course, $faculty, $topic, 'Which question is healthy?');
        $unsafeNameFaculty = User::factory()->create([
            'name' => '<script>alert(1)</script>',
            'role' => 'admin',
            'department_id' => $department->id,
        ]);
        $healthyItem->update(['author_id' => $unsafeNameFaculty->id]);
        $lowPassItem = $this->createQuestionBankItem($course, $faculty, $topic, 'Which question has a low pass rate?');
        $lowPassItem->update(['difficulty' => 'easy']);

        $firstSnapshot = $this->createQuestionSnapshot($department, $course, $faculty, $topic, $item);
        $secondSnapshot = $this->createQuestionSnapshot($department, $course, $faculty, $topic, $item);
        $legacySnapshot = $this->createQuestionSnapshot($department, $course, $faculty, $topic, $item, true);
        $healthySnapshot = $this->createQuestionSnapshot($department, $course, $faculty, $topic, $healthyItem);
        $lowPassSnapshot = $this->createQuestionSnapshot($department, $course, $faculty, $topic, $lowPassItem);

        foreach (['A', 'B', 'B', 'A', 'B', 'B'] as $index => $answer) {
            $this->createQuestionResponse(
                $student,
                $item,
                $index < 3 ? $firstSnapshot : $secondSnapshot,
                $answer,
                now()->subDays(6 - $index),
            );
        }
        $lastUsedAt = now()->subMinutes(10);
        $this->createQuestionResponse($student, $item, $legacySnapshot, 'A', $lastUsedAt, true);
        $this->createQuestionResponse($student, $healthyItem, $healthySnapshot, 'One', now()->subMinutes(3));
        $this->createQuestionResponse($student, $healthyItem, $healthySnapshot, 'Two', now()->subMinutes(2));
        foreach (['One', 'Two', 'Two', 'Three', 'Four'] as $index => $answer) {
            $this->createQuestionResponse(
                $student,
                $lowPassItem,
                $lowPassSnapshot,
                $answer,
                now()->subMinutes(5 - $index),
            );
        }

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertOk()
            ->assertSee('Needs Review')
            ->assertSee('Healthy')
            ->assertSee('42.9%', false)
            ->assertSee('(3/7)', false)
            ->assertSee('20.0%', false)
            ->assertSee('Student Pass Rate')
            ->assertSee('Difficulty Match')
            ->assertSee('Used in 3 exams')
            ->assertSee($lastUsedAt->format('M j, Y'))
            ->assertSee('0% picked')
            ->assertSee('Unused distractor')
            ->assertSee('Past exam results remain preserved; edits apply to future exams.')
            ->assertSee('Added by: Faculty')
            ->assertSee('Added by: &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('data-bs-title="Automatically classifies the question', false)
            ->assertSee('data-bs-title="Combines all student responses across every exam and quiz', false)
            ->assertSee('data-bs-title="Compares your assigned difficulty', false)
            ->assertSee('data-bs-title="Tracks how many exams or quizzes', false);

        $this->get(route('admin.question-bank.topics.show', [
            'topic' => $topic,
            'health' => 'needs_review',
        ]))
            ->assertOk()
            ->assertSee('Which choice is correct?')
            ->assertSee('Which question has a low pass rate?')
            ->assertDontSee('Which question has no responses?');

        $this->get(route('admin.question-bank.topics.show', [
            'topic' => $topic,
            'health' => 'healthy',
        ]))
            ->assertOk()
            ->assertSee('Which question is healthy?')
            ->assertDontSee('Which choice is correct?');

        $this->get(route('admin.question-bank.topics.show', [
            'topic' => $topic,
            'health' => 'untested',
        ]))
            ->assertOk()
            ->assertSee('Which question has no responses?')
            ->assertDontSee('Which choice is correct?');
    }

    public function test_only_peer_faculty_in_the_same_college_and_super_admin_can_flag_questions(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $creator = User::factory()->create([
            'name' => 'Question creator',
            'role' => 'admin',
            'department_id' => $department->id,
        ]);
        $peerFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $otherCollege = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $outsideFaculty = User::factory()->create(['role' => 'admin', 'department_id' => $otherCollege->id]);
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $topic = $this->createTopic($course, $creator, 'Network security');
        $item = $this->createQuestionBankItem($course, $creator, $topic, 'Which protocol is secure?');

        $this->actingAs($creator)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertSee('Added by: Question creator')
            ->assertDontSee('data-url="'.route('admin.question-bank.revision', $item).'"', false);

        $this->patchJson(route('admin.question-bank.revision', $item), ['needs_revision' => true])
            ->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'needs_revision' => false]);

        $this->actingAs($peerFaculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertSee('data-url="'.route('admin.question-bank.revision', $item).'"', false);

        $this->patchJson(route('admin.question-bank.revision', $item), ['needs_revision' => true])
            ->assertOk()
            ->assertExactJson([
                'needs_revision' => true,
                'quality_status' => 'flagged',
                'unflagged_quality_status' => 'untested',
            ]);
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'needs_revision' => true]);

        $this->actingAs($outsideFaculty)
            ->patchJson(route('admin.question-bank.revision', $item), ['needs_revision' => true])
            ->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'needs_revision' => true]);

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)
            ->patchJson(route('admin.question-bank.revision', $item), ['needs_revision' => false])
            ->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'needs_revision' => true]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.question-bank.index', [
                'department_id' => $department->id,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'health' => 'flagged',
            ]))
            ->assertSee('data-url="'.route('super-admin.question-bank.revision', $item).'"', false)
            ->assertSee('bi-flag-fill', false);

        $this->patchJson(route('super-admin.question-bank.revision', $item), ['needs_revision' => false])
            ->assertOk()
            ->assertExactJson([
                'needs_revision' => false,
                'quality_status' => 'untested',
                'unflagged_quality_status' => 'untested',
            ]);
        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'needs_revision' => false]);
    }

    public function test_faculty_can_create_a_question_for_a_program_in_their_college(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $topic = $this->createTopic($course, $faculty, 'Network security');

        $this->actingAs($faculty)
            ->post(route('admin.question-bank.store'), [
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'question' => 'Which protocol secures web traffic?',
                'options' => ['HTTP', 'FTP', 'HTTPS', 'SMTP'],
                'correct_choice' => 'C',
                'difficulty' => 'medium',
                'explanation' => 'HTTPS uses TLS to protect traffic.',
            ])
            ->assertRedirect();

        $item = QuestionBankItem::query()->firstOrFail();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'correct_answer' => 'HTTPS',
        ]);
        $this->assertSame(['HTTP', 'FTP', 'HTTPS', 'SMTP'], $item->options);
        $this->assertSame('medium', $item->difficulty);
        $this->assertNull($item->image_path);
    }

    public function test_faculty_can_add_an_image_and_difficulty_to_a_question(): void
    {
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $topic = $this->createTopic($course, $faculty, 'Algebra');

        $this->actingAs($faculty)
            ->post(route('admin.question-bank.store'), [
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'question' => 'Solve the equation shown.',
                'options' => ['2', '4', '6', '8'],
                'correct_choice' => 'B',
                'difficulty' => 'hard',
                'question_image' => UploadedFile::fake()->image('equation.png'),
            ])
            ->assertRedirect();

        $item = QuestionBankItem::query()->where('question', 'Solve the equation shown.')->firstOrFail();
        $this->assertSame('hard', $item->difficulty);
        $this->assertStringStartsWith('question-bank-images/', $item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
    }

    public function test_faculty_can_replace_or_remove_an_image_without_changing_exam_snapshots(): void
    {
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Algebra');
        $oldImagePath = 'question-bank-images/old-equation.png';
        Storage::disk('public')->put($oldImagePath, 'old image');
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'Solve the equation shown.',
            'options' => ['2', '4', '6', '8'],
            'correct_answer' => '4',
            'topic' => 'Algebra',
            'image_path' => $oldImagePath,
            'difficulty' => 'easy',
        ]);
        $exam = Exam::create([
            'title' => 'Algebra test',
            'topic' => 'Algebra',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);
        Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $item->id,
            'topic_id' => $topic->id,
            'question' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'topic' => $item->topic,
            'image_path' => $oldImagePath,
            'difficulty' => 'easy',
        ]);

        $this->actingAs($faculty)
            ->put(route('admin.question-bank.update', $item), [
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'question' => $item->question,
                'options' => $item->options,
                'correct_choice' => 'B',
                'difficulty' => 'hard',
                'question_image' => UploadedFile::fake()->image('replacement-equation.png'),
            ])
            ->assertRedirect();

        $item->refresh();
        $replacementImagePath = $item->image_path;
        $this->assertSame('hard', $item->difficulty);
        $this->assertNotSame($oldImagePath, $replacementImagePath);
        Storage::disk('public')->assertExists($replacementImagePath);
        Storage::disk('public')->assertExists($oldImagePath);
        $this->assertDatabaseHas('questions', [
            'test_id' => $exam->id,
            'image_path' => $oldImagePath,
            'difficulty' => 'easy',
        ]);

        $this->put(route('admin.question-bank.update', $item), [
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'question' => $item->question,
            'options' => $item->options,
            'correct_choice' => 'B',
            'difficulty' => 'hard',
            'remove_question_image' => 1,
        ])->assertRedirect();

        $this->assertNull($item->fresh()->image_path);
        $this->assertSame('hard', $item->fresh()->difficulty);
        Storage::disk('public')->assertMissing($replacementImagePath);
        Storage::disk('public')->assertExists($oldImagePath);
    }

    public function test_faculty_question_image_and_difficulty_are_validated(): void
    {
        Storage::fake('public');
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Algebra');

        $this->actingAs($faculty)
            ->post(route('admin.question-bank.store'), [
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'question' => 'Invalid image question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_choice' => 'A',
                'difficulty' => 'impossible',
                'question_image' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors(['difficulty', 'question_image']);

        $this->assertDatabaseMissing('question_bank_items', ['question' => 'Invalid image question?']);
    }

    public function test_faculty_cannot_update_or_delete_a_question_from_another_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $otherFaculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $otherDepartment->id,
            'course_id' => $otherCourse->id,
        ]);
        $item = QuestionBankItem::create([
            'course_id' => $otherCourse->id,
            'author_id' => $otherFaculty->id,
            'question' => 'Foreign program question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'topic' => 'Teaching',
        ]);

        $this->actingAs($faculty)
            ->put(route('admin.question-bank.update', $item), [
                'question' => 'Changed?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'topic' => 'Changed',
            ])
            ->assertNotFound();

        $this->delete(route('admin.question-bank.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'question' => 'Foreign program question?',
        ]);
    }

    public function test_faculty_cannot_edit_or_delete_another_facultys_question_in_the_same_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $questionOwner = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'author_id' => $questionOwner->id,
            'question' => 'Question owned by another Faculty member?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);

        $this->actingAs($faculty)
            ->put(route('admin.question-bank.update', $item), [
                'question' => 'Changed by another Faculty member?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'topic' => 'Changed',
            ])
            ->assertNotFound();

        $this->delete(route('admin.question-bank.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('question_bank_items', [
            'id' => $item->id,
            'author_id' => $questionOwner->id,
            'question' => 'Question owned by another Faculty member?',
        ]);
    }

    public function test_faculty_share_program_folders_and_questions_but_only_the_folder_creator_can_manage_them(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $folderCreator = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $folderCreator, 'Network security');
        $creatorItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $folderCreator->id,
            'question' => 'Question from the folder creator?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
        ]);
        $facultyItem = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'Question from another faculty member?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'B',
        ]);

        $this->actingAs($faculty)
            ->get(route('admin.question-bank.topics.show', $topic))
            ->assertOk()
            ->assertSee($topic->name)
            ->assertSee($creatorItem->question)
            ->assertSee($facultyItem->question)
            ->assertSee($folderCreator->name)
            ->assertDontSee('action="'.route('admin.question-bank.update', $creatorItem).'"', false)
            ->assertDontSee('action="'.route('admin.question-bank.destroy', $creatorItem).'"', false)
            ->assertSee('action="'.route('admin.question-bank.update', $facultyItem).'"', false)
            ->assertSee('action="'.route('admin.question-bank.destroy', $facultyItem).'"', false);

        $this->from(route('admin.question-bank.topics.show', $topic))
            ->actingAs($folderCreator)
            ->delete(route('admin.question-bank.topics.destroy', $topic))
            ->assertSessionHasErrors('topic');

        $this->assertDatabaseHas('topics', ['id' => $topic->id]);
        $this->assertDatabaseHas('question_bank_items', ['id' => $creatorItem->id]);
        $this->assertDatabaseHas('question_bank_items', ['id' => $facultyItem->id]);

        $this->actingAs($faculty)
            ->delete(route('admin.question-bank.topics.destroy', $topic))
            ->assertNotFound();
    }

    public function test_super_admin_can_browse_shared_question_folders_after_selecting_a_college_and_program(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network security');
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'What does TLS protect?',
            'options' => ['Confidentiality', 'Email routing', 'File compression', 'Power supply'],
            'correct_answer' => 'Confidentiality',
        ]);
        $admin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($admin)
            ->get(route('super-admin.question-bank.index'))
            ->assertOk()
            ->assertSee('id="question-bank-department"', false)
            ->assertSee('id="question-bank-course"', false)
            ->assertSee('id="question-bank-faculty"', false)
            ->assertSee('disabled', false)
            ->assertSee('data-bs-target="#questionBankFilterModal"', false)
            ->assertSee('data-question-bank-filter-trigger', false)
            ->assertSee('Select a college first')
            ->assertSee('Apply Filter')
            ->assertDontSee($item->question)
            ->assertDontSee('Create Topic')
            ->assertDontSee('Create Question');

        $this->get(route('super-admin.question-bank.index', ['department_id' => $department->id]))
            ->assertOk()
            ->assertSee($course->name)
            ->assertDontSee($item->question);

        $this->get(route('super-admin.question-bank.index', [
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]))
            ->assertOk()
            ->assertSee($topic->name)
            ->assertDontSee($item->question);

        $this->get(route('super-admin.question-bank.index', [
            'department_id' => $department->id,
            'course_id' => $course->id,
            'topic_id' => $topic->id,
        ]))
            ->assertOk()
            ->assertSee($item->question)
            ->assertSee($faculty->name)
            ->assertSee(route('super-admin.question-bank.status', $item))
            ->assertDontSee(route('admin.question-bank.update', $item))
            ->assertDontSee(route('admin.question-bank.destroy', $item))
            ->assertDontSee(route('admin.question-bank.store'))
            ->assertDontSee('Create Question')
            ->assertViewHas('readOnly', true);

        $this->post(route('admin.question-bank.store'), [
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'question' => 'Super Admin cannot create this question?',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_choice' => 'A',
            'difficulty' => 'easy',
        ])->assertRedirect();

        $this->assertDatabaseMissing('question_bank_items', [
            'course_id' => $course->id,
            'question' => 'Super Admin cannot create this question?',
        ]);
    }

    public function test_super_admin_can_filter_program_questions_by_faculty_across_folders(): void
    {
        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['name' => 'Avery Faculty', 'role' => 'admin', 'department_id' => $department->id]);
        $otherFaculty = User::factory()->create(['name' => 'Bree Faculty', 'role' => 'admin', 'department_id' => $department->id]);
        $firstTopic = $this->createTopic($course, $faculty, 'Networking');
        $secondTopic = $this->createTopic($course, $faculty, 'Security');
        $firstQuestion = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $firstTopic->id,
            'author_id' => $faculty->id,
            'question' => 'What does a router forward?',
            'options' => ['Packets', 'Screens', 'Power', 'Sound'],
            'correct_answer' => 'Packets',
        ]);
        $secondQuestion = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $secondTopic->id,
            'author_id' => $faculty->id,
            'question' => 'What protects a transmitted secret?',
            'options' => ['Encryption', 'Compression', 'Routing', 'Caching'],
            'correct_answer' => 'Encryption',
        ]);
        $otherQuestion = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $firstTopic->id,
            'author_id' => $otherFaculty->id,
            'question' => 'What does DNS resolve?',
            'options' => ['Names', 'Images', 'Ports', 'Colors'],
            'correct_answer' => 'Names',
        ]);

        $facultyFilteredResponse = $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.question-bank.index', [
                'department_id' => $department->id,
                'course_id' => $course->id,
                'faculty_id' => $faculty->id,
            ]))
            ->assertOk()
            ->assertSee($faculty->name)
            ->assertSee($firstTopic->name)
            ->assertSee($secondTopic->name)
            ->assertDontSee($firstQuestion->question)
            ->assertDontSee($secondQuestion->question)
            ->assertDontSee($otherQuestion->question)
            ->assertViewHas('selectedFacultyId', $faculty->id)
            ->assertViewHas('showTopicPage', false);

        $this->assertSame(
            1,
            $facultyFilteredResponse->viewData('topics')->firstWhere('id', $firstTopic->id)->questions_count,
        );

        $this->get(route('super-admin.question-bank.index', [
            'department_id' => $department->id,
            'course_id' => $course->id,
            'faculty_id' => $otherFaculty->id,
            'topic_id' => $firstTopic->id,
        ]))
            ->assertOk()
            ->assertSee($otherQuestion->question)
            ->assertDontSee($firstQuestion->question);
    }

    public function test_super_admin_must_select_the_matching_college_before_a_program(): void
    {
        $firstDepartment = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $firstCourse = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $firstDepartment->id]);
        $otherDepartment = Department::create(['name' => 'Education', 'code' => 'EDU']);
        $otherCourse = Course::create(['name' => 'Teaching', 'code' => 'TEACH', 'department_id' => $otherDepartment->id]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->get(route('super-admin.question-bank.index', ['course_id' => $firstCourse->id]))
            ->assertNotFound();

        $this->get(route('super-admin.question-bank.index', [
            'department_id' => $firstDepartment->id,
            'course_id' => $otherCourse->id,
        ]))->assertNotFound();

        $this->get(route('super-admin.question-bank.index', [
            'department_id' => $firstDepartment->id,
            'course_id' => $firstCourse->id,
        ]))->assertOk();
    }

    public function test_super_admin_can_disable_a_shared_question_and_notify_its_author(): void
    {
        Notification::fake();

        $department = Department::create(['name' => 'Computing', 'code' => 'COMP']);
        $course = Course::create(['name' => 'Software Engineering', 'code' => 'SE', 'department_id' => $department->id]);
        $faculty = User::factory()->create(['role' => 'admin', 'department_id' => $department->id]);
        $topic = $this->createTopic($course, $faculty, 'Network security');
        $item = QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $faculty->id,
            'question' => 'What does TLS protect?',
            'options' => ['Confidentiality', 'Email routing', 'File compression', 'Power supply'],
            'correct_answer' => 'Confidentiality',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super-admin']))
            ->patch(route('super-admin.question-bank.status', $item), ['is_active' => '0'])
            ->assertRedirect(route('super-admin.question-bank.index', [
                'department_id' => $department->id,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
            ]));

        $this->assertDatabaseHas('question_bank_items', ['id' => $item->id, 'is_active' => false]);
        Notification::assertSentTo($faculty, QuestionBankItemDisabledNotification::class, function ($notification, array $channels) use ($item): bool {
            return $notification->questionBankItem->is($item)
                && $channels === ['database']
                && str_contains($notification->toArray($item->author)['message'], $item->question);
        });

        $this->patch(route('super-admin.question-bank.status', $item), ['is_active' => '0']);
        $this->patch(route('super-admin.question-bank.status', $item), ['is_active' => '1']);
        Notification::assertSentTimes(QuestionBankItemDisabledNotification::class, 1);
    }

    private function createTopic(Course $course, User $creator, string $name): Topic
    {
        return Topic::create([
            'course_id' => $course->id,
            'created_by' => $creator->id,
            'name' => $name,
        ]);
    }

    private function createQuestionBankItem(
        Course $course,
        User $author,
        Topic $topic,
        string $question,
    ): QuestionBankItem {
        return QuestionBankItem::create([
            'course_id' => $course->id,
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'question' => $question,
            'options' => ['One', 'Two', 'Three', 'Four'],
            'correct_answer' => 'One',
            'topic' => $topic->name,
        ]);
    }

    private function createQuestionSnapshot(
        Department $department,
        Course $course,
        User $faculty,
        Topic $topic,
        QuestionBankItem $item,
        bool $legacySnapshot = false,
    ): Question {
        $exam = Exam::create([
            'title' => 'Question analytics exam',
            'topic' => $topic->name,
            'department_id' => $department->id,
            'course_id' => $course->id,
            'created_by' => $faculty->id,
            'is_general' => false,
            'is_scheduled_exam' => true,
            'is_published' => false,
            'approval_status' => Exam::APPROVAL_STATUS_PENDING,
            'available_from' => now()->addHour(),
        ]);

        return Question::create([
            'test_id' => $exam->id,
            'question_bank_item_id' => $legacySnapshot ? null : $item->id,
            'topic_id' => $topic->id,
            'question' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'topic' => $topic->name,
        ]);
    }

    private function createQuestionResponse(
        User $student,
        QuestionBankItem $item,
        Question $snapshot,
        string $answer,
        Carbon $createdAt,
        bool $legacyResponse = false,
    ): Result {
        $answerData = [
            'question_text' => $item->question,
            'options' => $item->options,
            'correct_answer' => $item->correct_answer,
            'user_answer' => $answer,
            'is_correct' => $answer === $item->correct_answer,
        ];
        if (! $legacyResponse) {
            $answerData['question_id'] = $snapshot->id;
            $answerData['question_bank_item_id'] = $item->id;
        }

        $result = Result::create([
            'user_id' => $student->id,
            'test_id' => $snapshot->test_id,
            'score' => $answer === $item->correct_answer ? 1 : 0,
            'total_questions' => 1,
            'answers' => [$answerData],
        ]);
        $result->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $result;
    }
}
