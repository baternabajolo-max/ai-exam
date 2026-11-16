<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\User as Account;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_page_shows_student_fields_before_optional_google_registration(): void
    {
        $response = $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('google.redirect'))
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="department_id"', false)
            ->assertSee('College')
            ->assertSee('Select college')
            ->assertDontSee('Select department')
            ->assertSee('name="course_id"', false)
            ->assertDontSee('name="student_id_number"', false)
            ->assertDontSee('Student ID number');

        $page = $response->getContent();
        $formPosition = strpos($page, 'action="'.route('register.submit').'"');
        $googlePosition = strpos($page, 'Continue with Google');

        $this->assertIsInt($formPosition);
        $this->assertIsInt($googlePosition);
        $this->assertGreaterThan($formPosition, $googlePosition);
    }

    public function test_google_sign_in_prompts_the_user_to_choose_an_account(): void
    {
        $this->configureGoogle();

        $this->get(route('google.redirect'))
            ->assertRedirect()
            ->assertRedirectContains('prompt=select_account');
    }

    public function test_student_registration_is_rejected_without_required_fields(): void
    {
        $this->from(route('register'))->post(route('register.submit'), [])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'name',
                'email',
                'password',
                'department_id',
                'course_id',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_registration_requires_eight_characters_an_uppercase_letter_and_a_symbol(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create([
            'name' => 'Biology',
            'code' => 'BIO',
            'department_id' => $department->id,
        ]);
        $invalidPasswords = [
            ['password' => 'short!', 'password_confirmation' => 'short!'],
            ['password' => 'lowercase!', 'password_confirmation' => 'lowercase!'],
            ['password' => 'Uppercase', 'password_confirmation' => 'Uppercase'],
        ];

        foreach ($invalidPasswords as $index => $passwords) {
            $this->from(route('register'))
                ->post(route('register.submit'), [
                    'name' => 'Taylor Student',
                    'email' => "taylor-{$index}@example.test",
                    ...$passwords,
                    'department_id' => $department->id,
                    'course_id' => $course->id,
                ])
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors('password');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_can_register_without_a_last_name(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create([
            'name' => 'Biology',
            'code' => 'BIO',
            'department_id' => $department->id,
        ]);
        Notification::fake();

        $this->post(route('register.submit'), [
            'name' => 'Taylor',
            'email' => 'taylor@example.test',
            'password' => 'StudentPass!',
            'password_confirmation' => 'StudentPass!',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $student = Account::query()->where('email', 'taylor@example.test')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Taylor',
            'role' => 'student',
            'status' => 'pending',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'student_id_number' => null,
            'email_verification_required' => true,
        ]);
        Notification::assertSentTo($student, VerifyEmail::class);
        $student->forceFill([
            'email_verified_at' => now(),
            'email_verification_required' => false,
            'status' => 'approved',
        ])->save();

        $this->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'StudentPass!',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_student_cannot_select_a_course_from_another_department(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $otherDepartment = Department::create(['name' => 'Arts', 'code' => 'ART']);
        $course = Course::create([
            'name' => 'Fine Arts',
            'code' => 'ART1',
            'department_id' => $otherDepartment->id,
        ]);
        $this->configureGoogle();
        Socialite::fake('google', User::fake([
            'id' => 'google-student-invalid-course',
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.test',
            'given_name' => 'Morgan',
            'family_name' => 'Lee',
            'email_verified' => true,
        ]));
        $this->get(route('google.callback'))->assertRedirect(route('register'));

        $this->from(route('register'))->post(route('register.submit'), [
            'department_id' => $department->id,
            'course_id' => $course->id,
        ])->assertRedirect(route('register'))
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_account_can_register_a_pending_student_using_verified_email(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $course = Course::create([
            'name' => 'Biology',
            'code' => 'BIO',
            'department_id' => $department->id,
        ]);
        $this->configureGoogle();
        Socialite::fake('google', User::fake([
            'id' => 'google-student-1001',
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.test',
            'given_name' => 'Morgan',
            'family_name' => 'Lee',
            'email_verified' => true,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('register'));

        Notification::fake();
        $this->post(route('register.submit'), [
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.test',
            'password' => 'StudentPassword123!',
            'password_confirmation' => 'StudentPassword123!',
            'department_id' => $department->id,
            'course_id' => $course->id,
        ])->assertRedirect(route('login'));
        $this->assertGuest();

        $student = Account::query()->where('email', 'morgan@example.test')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.test',
            'google_id' => 'google-student-1001',
            'role' => 'student',
            'status' => 'pending',
            'department_id' => $department->id,
            'course_id' => $course->id,
            'student_id_number' => null,
        ]);
        $this->assertNotNull($student->email_verified_at);
    }

    public function test_unverified_google_email_cannot_register(): void
    {
        $this->configureGoogle();
        Socialite::fake('google', User::fake([
            'id' => 'google-unverified-1001',
            'name' => 'Unverified Student',
            'email' => 'unverified@example.test',
            'email_verified' => false,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_sign_in_links_a_verified_email_to_an_existing_student_account(): void
    {
        $student = Account::factory()->create([
            'role' => 'student',
            'status' => 'approved',
            'email' => 'student@example.test',
            'google_id' => null,
            'email_verified_at' => null,
        ]);
        $student->forceFill(['email_verification_required' => true])->save();
        $this->configureGoogle();
        Socialite::fake('google', User::fake([
            'id' => 'google-student-1002',
            'name' => $student->name,
            'email' => $student->email,
            'email_verified' => true,
        ]));

        $response = $this->get(route('google.callback'))
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'google_id' => 'google-student-1002',
            'email_verification_required' => false,
        ]);
    }

    public function test_staff_account_cannot_authenticate_through_google(): void
    {
        Account::factory()->create([
            'role' => 'admin',
            'email' => 'staff@example.test',
        ]);
        $this->configureGoogle();
        Socialite::fake('google', User::fake([
            'id' => 'google-staff-1001',
            'name' => 'Department Admin',
            'email' => 'staff@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_existing_approved_student_can_sign_in_with_email_and_password(): void
    {
        $student = Account::factory()->create([
            'role' => 'student',
            'status' => 'approved',
            'email_verified_at' => null,
        ]);
        Notification::fake();

        $response = $this->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('student.dashboard'));

        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($student);
        Notification::assertNotSentTo($student, VerifyEmail::class);
    }

    public function test_existing_student_waits_for_approval_before_signing_in(): void
    {
        $student = Account::factory()->create([
            'role' => 'student',
            'status' => 'pending',
        ]);

        $this->from(route('login'))->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_signup_must_verify_its_email_before_student_can_sign_in(): void
    {
        $student = Account::factory()->create([
            'role' => 'student',
            'status' => 'approved',
            'email_verified_at' => null,
        ]);
        $student->forceFill(['email_verification_required' => true])->save();
        Notification::fake();

        $this->from(route('login'))->post(route('login.submit'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertGuest();
        Notification::assertSentTo($student, VerifyEmail::class);
    }

    public function test_signed_email_verification_link_verifies_a_student_account(): void
    {
        $student = Account::factory()->create([
            'role' => 'student',
            'status' => 'approved',
            'email_verified_at' => null,
        ]);
        $student->forceFill(['email_verification_required' => true])->save();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $student->id,
                'hash' => sha1($student->getEmailForVerification()),
            ],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your email is verified. You can now sign in.');

        $this->assertNotNull($student->fresh()->email_verified_at);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'email_verification_required' => false,
        ]);
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }
}
