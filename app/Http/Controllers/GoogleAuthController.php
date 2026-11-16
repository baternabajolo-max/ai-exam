<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->googleIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured yet. Please contact the system admin.',
            ]);
        }

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->googleIsConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in is not configured yet. Please contact the system admin.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (GuzzleException|IdentityProviderException|InvalidStateException $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }

        $googleData = $googleUser->getRaw();
        $googleId = trim((string) $googleUser->getId());
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if (
            $googleId === ''
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($googleData['email_verified'] ?? false) !== true
        ) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google must provide a verified email address to continue.',
            ]);
        }

        $account = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first();

        if ($account) {
            if ($account->role !== 'student') {
                return redirect()->route('login')->withErrors([
                    'email' => 'Administrator accounts must sign in with their staff credentials.',
                ]);
            }

            if ($account->google_id !== null && $account->google_id !== $googleId) {
                return redirect()->route('login')->withErrors([
                    'email' => 'This email is already linked to a different Google account.',
                ]);
            }

            $account->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $account->email_verified_at ?? now(),
                'email_verification_required' => false,
            ])->save();

            if ($account->status !== 'approved') {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your student account is pending admin approval.',
                ]);
            }

            Auth::login($account, true);
            $request->session()->regenerate(true);

            return redirect()->route('student.dashboard');
        }

        $name = trim((string) ($googleData['name'] ?? $googleUser->getName() ?? ''));
        $firstName = trim((string) ($googleData['given_name'] ?? ''));
        $lastName = trim((string) ($googleData['family_name'] ?? ''));

        if ($firstName === '' && $name !== '') {
            $nameParts = preg_split('/\s+/u', $name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $lastName !== '' ? $lastName : ($nameParts[1] ?? '');
        }

        if ($firstName === '') {
            $firstName = Str::before($email, '@');
        }

        $request->session()->put('google_registration', [
            'google_id' => $googleId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        return redirect()->route('register');
    }

    private function googleIsConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
