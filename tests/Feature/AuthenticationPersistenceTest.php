<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_login_issues_a_remember_cookie_by_default(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->get(route('login'))
            ->assertSee('Sign in with the email and password for your LEMU account, or continue with Google.');

        $response = $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($admin);
    }

    public function test_flash_messages_render_as_toasts_without_inline_duplicates(): void
    {
        $this->withSession(['success' => 'Your profile was updated.'])
            ->get(route('login'))
            ->assertSee('id="app-toast-container"', false)
            ->assertSee('toast-container position-fixed bottom-0 end-0 p-3', false)
            ->assertSee("className: 'toast-container position-fixed bottom-0 end-0 p-3'", false)
            ->assertSee('class="toast toast-success border-start border-4 border-success shadow-sm mb-2"', false)
            ->assertSee('#app-toast-container .toast.toast-success', false)
            ->assertSee('Your profile was updated.')
            ->assertDontSee('auth-alert success');
    }

    public function test_validation_errors_render_as_toasts_without_inline_duplicates(): void
    {
        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.submit'), [
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertSee('id="app-toast-container"', false)
            ->assertSee('class="toast toast-danger border-start border-4 border-danger shadow-sm mb-2"', false)
            ->assertSee('#app-toast-container .toast.toast-danger', false)
            ->assertSee('Please review the form')
            ->assertDontSee('auth-alert error');
    }

    public function test_authenticated_user_is_redirected_from_landing_to_dashboard(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'approved',
        ]);

        $this->actingAs($student)
            ->get(route('landing'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_dashboard_entry_redirects_to_the_analytics_dashboard(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('super-admin.dashboard'));
    }

    public function test_logout_ends_the_session_and_restores_public_landing_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'status' => 'approved',
        ]);

        $this->actingAs($student)
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
        $this->get(route('landing'))->assertOk()->assertViewIs('landing');
    }

    public function test_login_does_not_replace_an_existing_authenticated_account(): void
    {
        $currentUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $secondAdmin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($currentUser)
            ->post(route('login.submit'), [
                'email' => $secondAdmin->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($currentUser);
    }

    public function test_existing_admin_can_sign_in_without_email_verification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => null,
        ]);
        Notification::fake();

        $response = $this->post(route('login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($admin);
        Notification::assertNothingSentTo($admin);
    }

    public function test_existing_super_admin_can_sign_in_without_email_verification(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super-admin',
            'email_verified_at' => null,
        ]);
        Notification::fake();

        $response = $this->post(route('login.submit'), [
            'email' => $superAdmin->email,
            'password' => 'password',
        ])->assertRedirect(route('super-admin.dashboard'));

        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($superAdmin);
        Notification::assertNothingSentTo($superAdmin);
    }
}
