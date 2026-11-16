<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        $googleRegistration = session('google_registration');
        $departments = Department::with('courses')->orderBy('name')->get();

        return view('auth.register', compact('departments', 'googleRegistration'));
    }

    public function register(Request $request): RedirectResponse
    {
        $googleRegistration = session('google_registration');
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->symbols(), 'regex:/[A-Z]/'],
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'course_id' => [
                'required',
                Rule::exists('courses', 'id')->where('department_id', $request->input('department_id')),
            ],
            'student_id_number' => ['nullable', 'string', 'max:255', 'unique:users,student_id_number'],
        ]);

        $hasVerifiedGoogleRegistration = is_array($googleRegistration)
            && isset($googleRegistration['google_id'], $googleRegistration['email'])
            && Str::lower($googleRegistration['email']) === $validated['email'];

        $student = DB::transaction(function () use ($googleRegistration, $hasVerifiedGoogleRegistration, $validated): User {
            $student = User::create([
                'name' => trim($validated['name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'student',
                'status' => 'pending',
                'department_id' => $validated['department_id'],
                'course_id' => $validated['course_id'],
                'student_id_number' => $validated['student_id_number'] ?? null,
            ]);

            if ($hasVerifiedGoogleRegistration) {
                $student->forceFill([
                    'google_id' => $googleRegistration['google_id'],
                    'email_verified_at' => now(),
                ])->save();
            } else {
                $student->forceFill(['email_verification_required' => true])->save();
            }

            return $student;
        });

        $request->session()->forget('google_registration');

        if (! $hasVerifiedGoogleRegistration) {
            try {
                $student->sendEmailVerificationNotification();
            } catch (TransportExceptionInterface $exception) {
                report($exception);

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account was created, but the verification email could not be sent. Sign in again to retry.',
                ]);
            }
        }

        return redirect()->route('login')->with(
            'success',
            $hasVerifiedGoogleRegistration
                ? 'Registration submitted. Please wait for admin approval before signing in.'
                : 'Registration submitted. Verify your email, then wait for admin approval before signing in.',
        );
    }
}
