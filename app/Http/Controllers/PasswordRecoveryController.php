<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordRecoveryController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $staff = User::query()
            ->where('email', $validated['email'])
            ->whereIn('role', ['admin', 'super-admin'])
            ->first();

        if (! $staff) {
            return back()->with('status', 'If the address belongs to a staff account, a recovery link will be sent.');
        }

        try {
            $status = Password::sendResetLink(['email' => $staff->email]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'The recovery email could not be sent right now. Please try again later.',
            ]);
        }

        if ($status === Password::ResetLinkSent) {
            return back()->with('status', 'If the address belongs to a staff account, a recovery link will be sent.');
        }

        return back()->withErrors([
            'email' => 'The recovery email could not be sent right now. Please try again later.',
        ]);
    }

    public function showResetForm(Request $request, string $token): View
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        abort_unless(
            User::query()
                ->where('email', $request->input('email'))
                ->whereIn('role', ['admin', 'super-admin'])
                ->exists(),
            404,
        );

        return view('auth.reset-password', [
            'email' => $request->input('email'),
            'token' => $token,
            'setupMode' => false,
        ]);
    }

    public function showAdminSetupForm(Request $request, string $token): View
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        abort_unless(
            User::query()
                ->where('email', $request->input('email'))
                ->where('role', 'admin')
                ->exists(),
            404,
        );

        return view('auth.reset-password', [
            'email' => $request->input('email'),
            'token' => $token,
            'setupMode' => true,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $this->validatePasswordRequest($request);
        $staff = User::query()
            ->where('email', $validated['email'])
            ->whereIn('role', ['admin', 'super-admin'])
            ->first();

        if (! $staff) {
            return back()->withErrors([
                'email' => 'The password reset link is invalid or has expired.',
            ]);
        }

        $status = $this->updateStaffPassword($validated);

        if ($status === Password::PasswordReset) {
            return redirect()->route('login')->with('success', 'Your password is ready. Sign in with your staff credentials.');
        }

        return back()->withErrors([
            'email' => 'The password reset link is invalid or has expired.',
        ]);
    }

    public function setAdminPassword(Request $request): RedirectResponse
    {
        $validated = $this->validatePasswordRequest($request);
        $admin = User::query()
            ->where('email', $validated['email'])
            ->where('role', 'admin')
            ->first();

        if (! $admin) {
            return back()->withErrors([
                'email' => 'The password setup link is invalid or has expired.',
            ]);
        }

        $status = $this->updateStaffPassword($validated);

        if ($status === Password::PasswordReset) {
            return redirect()->route('login')->with('success', 'Your admin account is ready. Sign in with your new password.');
        }

        return back()->withErrors([
            'email' => 'The password setup link is invalid or has expired.',
        ]);
    }

    /**
     * @return array{token: string, email: string, password: string}
     */
    private function validatePasswordRequest(Request $request): array
    {
        return $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->symbols(),
                'regex:/[A-Z]/',
            ],
        ]);
    }

    /**
     * @param  array{token: string, email: string, password: string}  $validated
     */
    private function updateStaffPassword(array $validated): string
    {
        return Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                event(new PasswordReset($user));
            },
        );
    }
}
