<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AdminAccountSetupNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_password_recovery_and_reset_pages_render_password_only_flows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = Password::createToken($admin);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Email recovery link')
            ->assertSee('Continue with Google')
            ->assertDontSee('staff');

        $this->get(route('login'))
            ->assertSee('Forgot password?')
            ->assertDontSee('Forgot staff password?')
            ->assertSeeInOrder(['id="password"', 'Forgot password?'], false);

        $this->get(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Set a new password')
            ->assertSee('Continue with Google')
            ->assertDontSee('<label for="email">', false);

        $this->get(route('password.setup', ['token' => $token, 'email' => $admin->email]))
            ->assertOk()
            ->assertSee('Create your password')
            ->assertSee('Set password')
            ->assertDontSee('Password recovery');
    }

    public function test_staff_can_request_a_password_recovery_link(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => null,
        ]);
        Notification::fake();

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($admin, ResetPassword::class);
    }

    public function test_students_do_not_receive_staff_password_reset_links(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Notification::fake();

        $this->post(route('password.email'), ['email' => $student->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_student_cannot_use_a_password_reset_token(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = Password::createToken($student);
        Http::preventStrayRequests();

        $this->from(route('password.reset', ['token' => $token, 'email' => $student->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $student->email,
                'password' => 'LemuReset!Only',
                'password_confirmation' => 'LemuReset!Only',
            ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $student->fresh()->password));
    }

    public function test_password_reset_changes_the_password_and_verifies_staff_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => null,
        ]);
        $token = Password::createToken($admin);
        Http::preventStrayRequests();

        $this->from(route('password.reset', ['token' => $token, 'email' => $admin->email]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $admin->email,
                'password' => 'LemuReset!Only',
                'password_confirmation' => 'LemuReset!Only',
            ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('LemuReset!Only', $admin->fresh()->password));
        $this->assertNotNull($admin->fresh()->email_verified_at);
    }

    public function test_admin_can_set_a_password_from_an_admin_setup_link_while_another_staff_user_is_signed_in(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => null,
        ]);
        $signedInSuperAdmin = User::factory()->create(['role' => 'super-admin']);
        $token = Password::createToken($admin);
        Http::preventStrayRequests();

        $this->actingAs($signedInSuperAdmin)
            ->from(route('password.setup', ['token' => $token, 'email' => $admin->email]))
            ->post(route('password.setup.store'), [
                'token' => $token,
                'email' => $admin->email,
                'password' => 'LemuSetup!Only',
                'password_confirmation' => 'LemuSetup!Only',
            ])->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your admin account is ready. Sign in with your new password.');

        $this->assertTrue(Hash::check('LemuSetup!Only', $admin->fresh()->password));
        $this->assertNotNull($admin->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($signedInSuperAdmin);
    }

    public function test_super_admin_cannot_use_an_admin_setup_link(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $token = Password::createToken($superAdmin);
        Http::preventStrayRequests();

        $this->from(route('password.setup', ['token' => $token, 'email' => $superAdmin->email]))
            ->post(route('password.setup.store'), [
                'token' => $token,
                'email' => $superAdmin->email,
                'password' => 'LemuSetup!Only',
                'password_confirmation' => 'LemuSetup!Only',
            ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $superAdmin->fresh()->password));
    }

    public function test_super_admin_can_update_an_admin_password_without_numbers(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.users.update', $admin), [
                'password' => 'AdminPass!',
                'password_confirmation' => 'AdminPass!',
            ])
            ->assertRedirect(route('super-admin.users.index'))
            ->assertSessionHas('success', 'Faculty password updated successfully.');

        $this->assertTrue(Hash::check('AdminPass!', $admin->fresh()->password));
    }

    public function test_admin_creation_sends_a_password_setup_link_without_a_temporary_password(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create(['name' => 'Biology', 'code' => 'BIO', 'department_id' => $department->id]);
        Notification::fake();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.users.store'), [
                'name' => 'New Admin',
                'email' => 'new-admin@example.test',
                'department_id' => $department->id,
                'course_id' => $course->id,
            ])->assertRedirect(route('super-admin.users.index'))
            ->assertSessionHas('success');

        $admin = User::where('email', 'new-admin@example.test')->firstOrFail();
        $this->assertNull($admin->email_verified_at);
        $this->assertFalse(Hash::check('password', $admin->password));
        Notification::assertSentTo(
            $admin,
            AdminAccountSetupNotification::class,
            function (AdminAccountSetupNotification $notification) use ($admin): bool {
                $message = $notification->toMail($admin);

                return $message->subject === 'Set up your LEMU faculty password'
                    && $message->actionUrl === route('password.setup', [
                        'token' => $notification->token,
                        'email' => $admin->email,
                    ]);
            },
        );
    }
}
