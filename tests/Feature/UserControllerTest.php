<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AdminAccountSetupNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_faculty_directory_hides_the_question_bank_column(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.users.index'))
            ->assertSee('Faculty Accounts')
            ->assertSee($department->name)
            ->assertDontSee('<th>Question Bank</th>', false);
    }

    public function test_admin_can_assign_multiple_faculty_members_to_the_same_college(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        Notification::fake();

        foreach (['Faculty One', 'Faculty Two'] as $index => $name) {
            $this->actingAs($superAdmin)
                ->post(route('super-admin.users.store'), [
                    'name' => $name,
                    'email' => 'faculty-'.$index.'@example.test',
                    'department_id' => $department->id,
                ])
                ->assertRedirect(route('super-admin.users.index'))
                ->assertSessionHas('success');
        }

        $this->assertSame(
            2,
            User::query()
                ->where('role', 'admin')
                ->where('department_id', $department->id)
                ->whereNull('course_id')
                ->count(),
        );
    }

    public function test_resending_an_invitation_replaces_the_old_token_and_hides_action_after_activation(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create(['name' => 'Biology', 'code' => 'BIO', 'department_id' => $department->id]);
        $faculty = User::factory()->unverified()->create([
            'role' => 'admin',
            'status' => 'approved',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        $oldToken = Password::createToken($faculty);
        Notification::fake();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.users.resend-invitation', $faculty))
            ->assertRedirect(route('super-admin.users.index'))
            ->assertSessionHas('success');

        $newToken = null;
        Notification::assertSentTo(
            $faculty,
            AdminAccountSetupNotification::class,
            function (AdminAccountSetupNotification $notification) use (&$newToken): bool {
                $newToken = $notification->token;

                return true;
            },
        );
        $this->assertNotNull($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $this->from(route('password.setup', ['token' => $oldToken, 'email' => $faculty->email]))
            ->post(route('password.setup.store'), [
                'token' => $oldToken,
                'email' => $faculty->email,
                'password' => 'LemuSetup!Only',
                'password_confirmation' => 'LemuSetup!Only',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->from(route('password.setup', ['token' => $newToken, 'email' => $faculty->email]))
            ->post(route('password.setup.store'), [
                'token' => $newToken,
                'email' => $faculty->email,
                'password' => 'LemuSetup!Only',
                'password_confirmation' => 'LemuSetup!Only',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertNotNull($faculty->fresh()->email_verified_at);
        $this->actingAs($superAdmin)
            ->get(route('super-admin.users.index'))
            ->assertDontSee(route('super-admin.users.resend-invitation', $faculty));
    }

    public function test_active_faculty_invitation_cannot_be_resent(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create(['name' => 'Biology', 'code' => 'BIO', 'department_id' => $department->id]);
        $faculty = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ]);
        Notification::fake();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.users.resend-invitation', $faculty))
            ->assertRedirect(route('super-admin.users.index'))
            ->assertSessionHas('error', 'This faculty account is already active.');
    }
}
