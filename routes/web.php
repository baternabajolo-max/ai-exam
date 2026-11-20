<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ExamResultsController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ItemAnalysisController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\StudentActivityController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentLearnController;
use App\Http\Controllers\StudentResultController;
use App\Http\Controllers\StudentTestController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TestGenerationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerifyStudentEmailController;
use App\Http\Middleware\TrackFacultyActivity;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/api/departments/{department}/courses', function (Department $department) {
    return response()->json($department->courses);
});

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('landing');
})->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.submit');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

Route::get('/forgot-password', [PasswordRecoveryController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/forgot-password', [PasswordRecoveryController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [PasswordRecoveryController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [PasswordRecoveryController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
    ->name('password.update');
Route::get('/setup-password/{token}', [PasswordRecoveryController::class, 'showAdminSetupForm'])
    ->name('password.setup');
Route::post('/setup-password', [PasswordRecoveryController::class, 'setAdminPassword'])
    ->middleware('throttle:5,1')
    ->name('password.setup.store');

Route::get('/verify-email/{id}/{hash}', [VerifyStudentEmailController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Super Admin Group
Route::middleware(['auth', 'role:super-admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/leaderboard', fn () => redirect()->route('super-admin.results.index', [
        'college_id' => request('department_id'),
    ]))->name('leaderboard');
    Route::get('/results', [ExamResultsController::class, 'adminIndex'])->name('results.index');
    Route::get('/results/{exam}/student/{student}', [ExamResultsController::class, 'adminStudent'])->name('results.student');
    Route::get('/monitoring', [SuperAdminController::class, 'monitoring'])->name('monitoring');
    Route::get('/monitoring/colleges/{department}/programs', [SuperAdminController::class, 'monitoringPrograms'])
        ->name('monitoring.programs');
    Route::get('/monitoring/programs/{course}', [SuperAdminController::class, 'monitoringProgramDetails'])
        ->name('monitoring.program-details');
    Route::get('/monitoring/faculty/{faculty}/contributions', [SuperAdminController::class, 'monitoringFacultyContributions'])
        ->name('monitoring.faculty-contributions');
    Route::post('/monitoring/faculty/{faculty}/message', [SuperAdminController::class, 'sendFacultyMessage'])
        ->name('monitoring.faculty-message');
    Route::get('/modules', [ModuleController::class, 'adminIndex'])->name('modules.index');
    Route::get('/modules/{module}', [ModuleController::class, 'adminShow'])->name('modules.show');
    Route::get('/modules/{module}/students/{student}/overview', [StudentActivityController::class, 'adminModuleStudentOverview'])
        ->name('modules.students.overview');
    Route::get('/modules/{module}/students/{student}/activity', [StudentActivityController::class, 'adminModuleStudentActivity'])
        ->name('modules.students.activity');
    Route::post('/modules/{module}/students/{student}/notifications', [ModuleController::class, 'sendStudentNotice'])
        ->name('modules.students.notifications.store');
    Route::patch('/modules/subtopics/{subtopic}/revision', [ModuleController::class, 'adminFlagSubtopic'])->name('modules.subtopics.revision');
    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
    Route::get('/tests/{test}/edit', [TestController::class, 'edit'])->name('tests.edit');
    Route::put('/tests/{test}', [TestController::class, 'update'])->name('tests.update');
    Route::get('/tests/{test}/print', [TestController::class, 'print'])->name('tests.print');
    Route::post('/tests', [TestController::class, 'store'])->name('tests.store-manual');
    Route::post('/tests/{test}/approve', [TestController::class, 'approve'])->name('tests.approve');
    Route::post('/tests/{test}/reject', [TestController::class, 'reject'])->name('tests.reject');
    Route::delete('/tests/{test}', [TestController::class, 'destroy'])->name('tests.destroy');
    Route::get('/students/search/suggestions', [AdminController::class, 'studentSearchSuggestions'])->name('students.search-suggestions');
    Route::get('/students/{student}', [AdminController::class, 'showStudentGeneralTestHistory'])->name('students.show');
    Route::get('/students/{student}/results/{result}', [StudentResultController::class, 'adminShow'])->name('students.results.show');
    Route::post('/students/{student}/tests/{test}/retake', [AdminController::class, 'activateExamRetake'])->name('students.tests.retake');
    Route::get('/question-bank', [QuestionBankController::class, 'adminIndex'])->name('question-bank.index');
    Route::patch('/question-bank/{questionBankItem}/status', [QuestionBankController::class, 'updateStatus'])->name('question-bank.status');
    Route::patch('/question-bank/{questionBankItem}/revision', [QuestionBankController::class, 'updateRevisionFlag'])->name('question-bank.revision');
    Route::get('/item-analysis', [ItemAnalysisController::class, 'adminIndex'])->name('item-analysis.index');
    Route::get('/profile', [SuperAdminController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/admins', [UserController::class, 'store'])->name('admin.store');
    Route::delete('/admins/{admin}', [SuperAdminController::class, 'deleteAdmin'])->name('admin.delete');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::post('/departments/{department}/courses', [DepartmentController::class, 'storeCourse'])->name('departments.courses.store');
    Route::put('/courses/{course}', [DepartmentController::class, 'updateCourse'])->name('courses.update');
    Route::delete('/courses/{course}', [DepartmentController::class, 'deleteCourse'])->name('courses.destroy');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])
        ->middleware('throttle:5,1')
        ->name('users.resend-invitation');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/achievements/interpret-requirements', [AchievementController::class, 'parseRequirements'])->middleware('throttle:5,1')->name('achievements.interpret-requirements');
});

// Student Group
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'update'])->name('profile.update');
    Route::get('/results', [StudentResultController::class, 'index'])->name('results.index');
    Route::get('/results/{result}/print', [StudentResultController::class, 'print'])->name('results.print');
    Route::get('/results/{result}', [StudentResultController::class, 'show'])->name('results.show');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/achievements/{achievement}/claim', [StudentController::class, 'claimAchievement'])->name('achievements.claim');

    Route::get('/reviewers', [StudentController::class, 'reviewers'])->name('reviewers');
    Route::get('/activity', [StudentActivityController::class, 'index'])->name('activity.index');
    Route::get('/activity/topics/{subtopic}/quizzes', [StudentActivityController::class, 'topicQuizzes'])->name('activity.topics.quizzes');
    Route::post('/activity/sessions', [StudentActivityController::class, 'store'])->name('activity.sessions.store');
    Route::post('/activity/sessions/{studySession}/heartbeat', [StudentActivityController::class, 'heartbeat'])->name('activity.sessions.heartbeat');
    Route::post('/study-heartbeat', [StudentActivityController::class, 'heartbeatCurrent'])->name('study-heartbeat');
    Route::post('/activity/sessions/{studySession}/stop', [StudentActivityController::class, 'stop'])->name('activity.sessions.stop');

    Route::get('/tests', [StudentTestController::class, 'index'])->name('tests.index');
    Route::get('/exams', [StudentTestController::class, 'exams'])->name('exams.index');
    Route::post('/exams/{test}/start', [StudentTestController::class, 'startExam'])->name('exams.start');
    Route::get('/tests/generate-general', [StudentTestController::class, 'generateAndTakeGeneral'])->name('tests.generate-general');
    Route::get('/tests/generate-targeted/{topic?}', [StudentTestController::class, 'generateTargetedTest'])->name('tests.generate-targeted');

    Route::get('/tests/{test}', [StudentTestController::class, 'show'])->name('tests.show');
    Route::get('/tests/{test}/build', [StudentTestController::class, 'buildQuestions'])->name('tests.build');
    Route::post('/tests/{test}/submit', [StudentTestController::class, 'submit'])->name('tests.submit');

    Route::get('/learn', [StudentLearnController::class, 'index'])->name('learn.index');
    Route::get('/learn/{module}', [StudentLearnController::class, 'show'])->name('learn.show');
    Route::get('/learn/subtopic/{subtopic}', [StudentLearnController::class, 'review'])->name('learn.review');
    Route::get('/learn/subtopic/{subtopic}/extraction-status', [StudentLearnController::class, 'extractionStatus'])->name('learn.subtopic.extraction-status');
    Route::post('/learn/explain', [StudentLearnController::class, 'explainText'])->name('learn.explain');
    Route::get('/learn/subtopic/{subtopic}/quiz', [StudentTestController::class, 'quiz'])->name('learn.quiz');

    Route::get('/achievements', [StudentController::class, 'achievements'])->name('achievements');
    Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards.index');
    Route::get('/flashcards/print', [FlashcardController::class, 'print'])->name('flashcards.print');
    Route::post('/flashcards', [FlashcardController::class, 'store'])->name('flashcards.store');
    Route::put('/flashcards/{flashcard}', [FlashcardController::class, 'update'])->name('flashcards.update');
    Route::delete('/flashcards/{flashcard}', [FlashcardController::class, 'destroy'])->name('flashcards.destroy');
});

// Admin Group
Route::middleware(['auth', 'role:admin', TrackFacultyActivity::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/students', [AdminController::class, 'students'])->name('students');
    Route::get('/students/search/suggestions', [AdminController::class, 'studentSearchSuggestions'])->name('students.search-suggestions');
    Route::get('/students/{student}', [AdminController::class, 'showStudent'])->name('students.show');
    Route::get('/students/{student}/activity', [StudentActivityController::class, 'adminIndex'])->name('students.activity.index');
    Route::get('/students/{student}/activity/topics/{subtopic}/quizzes', [StudentActivityController::class, 'adminTopicQuizzes'])->name('students.activity.topics.quizzes');
    Route::get('/students/{student}/results/{result}', [StudentResultController::class, 'adminShow'])->name('students.results.show');
    Route::post('/students/{student}/tests/{test}/retake', [AdminController::class, 'activateExamRetake'])->name('students.tests.retake');
    Route::post('/students/{student}/notifications', [AdminController::class, 'sendNotification'])->name('students.notifications.store');
    Route::get('/leaderboard', fn () => redirect()->route('faculty.results.index'))->name('leaderboard');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/students/{student}/approve', [AdminController::class, 'approveStudent'])->name('students.approve');
    Route::post('/students/{student}/reject', [AdminController::class, 'rejectStudent'])->name('students.reject');

    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
    Route::get('/tests/{test}/edit', [TestController::class, 'edit'])->name('tests.edit');
    Route::put('/tests/{test}', [TestController::class, 'update'])->name('tests.update');
    Route::get('/tests/{test}/print', [TestController::class, 'print'])->name('tests.print');
    Route::delete('/tests/{test}', [TestController::class, 'destroy'])->name('tests.destroy');
    Route::post('/tests/manual', [TestController::class, 'store'])->name('tests.store-manual');
    Route::get('/tests/generate', [TestGenerationController::class, 'create'])->name('tests.generate');
    Route::post('/tests/generate', [TestGenerationController::class, 'store'])->name('tests.store');
    Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::post('/question-bank/topics', [QuestionBankController::class, 'storeTopic'])->name('question-bank.topics.store');
    Route::get('/question-bank/topics/{topic}', [QuestionBankController::class, 'showTopic'])->name('question-bank.topics.show');
    Route::put('/question-bank/topics/{topic}', [QuestionBankController::class, 'updateTopic'])->name('question-bank.topics.update');
    Route::delete('/question-bank/topics/{topic}', [QuestionBankController::class, 'destroyTopic'])->name('question-bank.topics.destroy');
    Route::post('/question-bank', [QuestionBankController::class, 'store'])->name('question-bank.store');
    Route::patch('/question-bank/{questionBankItem}/status', [QuestionBankController::class, 'updateStatus'])->name('question-bank.status');
    Route::patch('/question-bank/{questionBankItem}/revision', [QuestionBankController::class, 'updateRevisionFlag'])->name('question-bank.revision');
    Route::put('/question-bank/{questionBankItem}', [QuestionBankController::class, 'update'])->name('question-bank.update');
    Route::delete('/question-bank/{questionBankItem}', [QuestionBankController::class, 'destroy'])->name('question-bank.destroy');
    Route::get('/item-analysis', [ItemAnalysisController::class, 'facultyIndex'])->name('item-analysis.index');
    Route::get('/item-analysis/export', [ItemAnalysisController::class, 'exportFaculty'])->name('item-analysis.export');

    // Module Management
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::get('/modules/{module}', [ModuleController::class, 'show'])->name('modules.show');
    Route::get('/modules/{module}/students/{student}/overview', [StudentActivityController::class, 'adminModuleStudentOverview'])
        ->name('modules.students.overview');
    Route::get('/modules/{module}/students/{student}/activity', [StudentActivityController::class, 'adminModuleStudentActivity'])
        ->name('modules.students.activity');
    Route::post('/modules/{module}/students/{student}/notifications', [ModuleController::class, 'sendStudentNotice'])
        ->name('modules.students.notifications.store');
    Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');

    // Subtopic Management (File Uploads & Edits)
    Route::post('/modules/{module}/subtopics', [ModuleController::class, 'storeSubtopic'])->name('modules.subtopics.store');
    Route::put('/subtopics/{subtopic}', [ModuleController::class, 'updateSubtopic'])->name('subtopics.update');
    Route::delete('/subtopics/{subtopic}', [ModuleController::class, 'destroySubtopic'])->name('subtopics.destroy');
});

Route::middleware(['auth', 'role:admin', TrackFacultyActivity::class])
    ->prefix('faculty')
    ->name('faculty.')
    ->group(function () {
        Route::get('/results', [ExamResultsController::class, 'facultyIndex'])->name('results.index');
        Route::get('/results/{exam}/student/{student}', [ExamResultsController::class, 'facultyStudent'])->name('results.student');
    });

Route::get('/clean-slate', function () {
    // 1. Temporarily disable foreign key constraints to force the wipe
    Schema::disableForeignKeyConstraints();

    // 2. Wipe the child tables first, then the parent tests table
    if (Schema::hasTable('weak_areas')) {
        DB::table('weak_areas')->truncate();
    }
    if (Schema::hasTable('questions')) {
        DB::table('questions')->truncate();
    }

    DB::table('results')->truncate();
    DB::table('tests')->truncate();

    // 3. Re-enable foreign key constraints
    Schema::enableForeignKeyConstraints();

    // 4. Reset all students' points back to 0
    DB::table('users')
        ->where('role', 'student')
        ->update(['total_points' => 0]);

    // 5. Reset achievements pivot table so badges can be earned again
    DB::table('user_achievements')->truncate();

    return 'Clean slate successful! All ghost data is gone, achievements are reset, and all users are at 0 points.';
});
