<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate(true);

            $user = Auth::user();

            if ($user->role === 'student') {
                if ($user->email_verification_required && $user->email_verified_at === null) {
                    try {
                        $user->sendEmailVerificationNotification();
                    } catch (TransportExceptionInterface $exception) {
                        report($exception);
                        $this->clearAuthenticatedSession($request);

                        return back()->withErrors([
                            'email' => 'Your email is not verified, and the verification link could not be sent. Please try again later.',
                        ])->onlyInput('email');
                    }

                    $this->clearAuthenticatedSession($request);

                    return redirect()->route('login')->with(
                        'success',
                        'Verify your email using the link we sent before signing in.',
                    );
                }

                if ($user->status !== 'approved') {
                    $this->clearAuthenticatedSession($request);

                    return back()->withErrors([
                        'email' => 'Your student account is pending admin approval.',
                    ])->onlyInput('email');
                }

                return redirect()->route('student.dashboard');
            }

            if (! in_array($user->role, ['admin', 'super-admin'], true)) {
                $this->clearAuthenticatedSession($request);

                return back()->withErrors(['email' => 'This account cannot sign in using staff credentials.']);
            }

            return redirect()->route($user->role === 'super-admin' ? 'super-admin.dashboard' : 'admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->clearAuthenticatedSession($request);

        return redirect()->route('landing');
    }

    private function clearAuthenticatedSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
