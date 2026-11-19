<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\StudentNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentNotificationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_navigation_and_all_notifications_page_show_unread_messages(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'name' => 'Ada Administrator',
        ]);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => $admin->id,
            'subject' => 'Review reminder',
            'message' => 'Please take this week’s quiz.',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('notificationsDropdown')
            ->assertSee('Review reminder')
            ->assertSee('View all notifications')
            ->assertSee('notification-modal-trigger')
            ->assertSee('notification-dropdown__list')
            ->assertSee('data-bs-target="#notificationModal"', false)
            ->assertSee(route('student.notifications.index'), false);

        $this->actingAs($student)
            ->get(route('student.notifications.index', ['notification' => $student->receivedNotifications()->first()->id]))
            ->assertSee('Please take this week’s quiz.')
            ->assertSee('Ada Administrator')
            ->assertSee('Mark all as read')
            ->assertDontSee('Go back')
            ->assertDontSee('Your updates')
            ->assertDontSee('Keep track of important messages and activity.')
            ->assertSee('Inbox')
            ->assertSee('notifications-page__sidebar', false)
            ->assertSee('notifications-page__reader', false)
            ->assertSee('Close notification');
    }

    public function test_student_nav_shows_only_five_recent_notifications(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        foreach (range(1, 7) as $number) {
            $notification = StudentNotification::create([
                'student_id' => $student->id,
                'admin_id' => null,
                'subject' => "Notice {$number}",
                'message' => "Message {$number}",
            ]);
            $notification->forceFill(['created_at' => now()->addMinutes($number)])->save();
        }

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee('Notice 3')
            ->assertSee('Notice 7')
            ->assertDontSee('Notice 1')
            ->assertDontSee('Notice 2')
            ->assertSee('notification-dropdown__list');
    }

    public function test_student_notification_detail_is_marked_read_when_closed_and_returns_to_previous_page(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $notification = StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => null,
            'subject' => 'Course update',
            'message' => 'The exam schedule changed.',
        ]);
        $returnTo = '/student/exams?program=engineering';

        $this->actingAs($student)
            ->get(route('student.notifications.index', [
                'notification' => $notification->id,
                'return_to' => $returnTo,
            ]))
            ->assertSee('The exam schedule changed.')
            ->assertSee('data-notification-detail', false)
            ->assertSee('data-notification-read="false"', false)
            ->assertSee('name="close_detail"', false)
            ->assertDontSee('name="leave_page"', false);

        $this->assertNull($notification->fresh()->read_at);

        $this->post(route('student.notifications.read', $notification), [
            'close_detail' => 1,
            'return_to' => $returnTo,
        ])
            ->assertRedirect(route('student.notifications.index', ['return_to' => $returnTo]));

        $this->assertNotNull($notification->fresh()->read_at);

        $this->post(route('student.notifications.read', $notification), [
            'leave_page' => 1,
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo);
    }

    public function test_student_can_mark_an_owned_notification_as_read(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notification = StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => null,
            'subject' => 'Badge unlocked',
            'message' => 'Claim your badge.',
        ]);

        $this->actingAs($student)
            ->post(route('student.notifications.read', $notification))
            ->assertRedirect();

        $this->assertDatabaseMissing('student_notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_student_cannot_mark_another_students_notification_as_read(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $firstStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notification = StudentNotification::create([
            'student_id' => $otherStudent->id,
            'admin_id' => null,
            'subject' => 'Private notice',
            'message' => 'Private message.',
        ]);

        $this->actingAs($firstStudent)
            ->post(route('student.notifications.read', $notification))
            ->assertNotFound();

        $this->assertDatabaseHas('student_notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_student_can_mark_all_owned_notifications_as_read(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notifications = collect(['First message', 'Second message'])->map(fn (string $subject) => StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => null,
            'subject' => $subject,
            'message' => 'System update.',
        ]));
        $otherNotification = StudentNotification::create([
            'student_id' => $otherStudent->id,
            'admin_id' => null,
            'subject' => 'Other student message',
            'message' => 'Must stay unread.',
        ]);

        $this->actingAs($student)
            ->post(route('student.notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ($notifications as $notification) {
            $this->assertDatabaseMissing('student_notifications', [
                'id' => $notification->id,
                'read_at' => null,
            ]);
        }
        $this->assertDatabaseHas('student_notifications', [
            'id' => $otherNotification->id,
            'read_at' => null,
        ]);
    }

    public function test_student_can_delete_an_owned_notification(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notification = StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => null,
            'subject' => 'Old update',
            'message' => 'No longer needed.',
        ]);

        $this->actingAs($student)
            ->delete(route('student.notifications.destroy', $notification))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('student_notifications', ['id' => $notification->id]);
    }

    public function test_student_cannot_delete_another_students_notification(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notification = StudentNotification::create([
            'student_id' => $otherStudent->id,
            'admin_id' => null,
            'subject' => 'Private notice',
            'message' => 'Private message.',
        ]);

        $this->actingAs($student)
            ->delete(route('student.notifications.destroy', $notification))
            ->assertNotFound();

        $this->assertModelExists($notification);
    }

    public function test_student_can_delete_all_owned_notifications_without_deleting_another_students(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $otherStudent = User::factory()->create(['role' => 'student', 'department_id' => $department->id]);
        $notifications = collect(['First update', 'Second update'])->map(fn (string $subject) => StudentNotification::create([
            'student_id' => $student->id,
            'admin_id' => null,
            'subject' => $subject,
            'message' => 'System update.',
        ]));
        $otherNotification = StudentNotification::create([
            'student_id' => $otherStudent->id,
            'admin_id' => null,
            'subject' => 'Private update',
            'message' => 'Must remain.',
        ]);

        $this->actingAs($student)
            ->delete(route('student.notifications.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('success', 'All notifications have been deleted.');

        foreach ($notifications as $notification) {
            $this->assertDatabaseMissing('student_notifications', ['id' => $notification->id]);
        }
        $this->assertModelExists($otherNotification);
    }
}
