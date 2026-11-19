<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Notifications\AdminAccountSetupNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')
            ->with('department')
            ->latest()
            ->get();
        $departments = Department::orderBy('name')->get();

        return view('super-admin.users.index', compact('admins', 'departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'department_id' => ['required', 'exists:departments,id'],
            'course_ids' => ['prohibited'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(64)),
            'role' => 'admin',
            'status' => 'approved',
            'department_id' => $validated['department_id'],
            'course_id' => null,
        ]);

        try {
            $token = Password::createToken($admin);
            $admin->notify(new AdminAccountSetupNotification($token));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('super-admin.users.index')->withErrors([
                'email' => 'The faculty account was created, but its setup email could not be sent. Retry using Resend Invitation.',
            ]);
        }

        return redirect()->route('super-admin.users.index')->with(
            'success',
            'Faculty account created. A password setup link was sent.',
        );
    }

    public function resendInvitation(User $user): RedirectResponse
    {
        if ($user->role !== 'admin') {
            return redirect()->route('super-admin.users.index')->with('error', 'Only faculty invitations can be resent here.');
        }

        if ($user->email_verified_at !== null) {
            return redirect()->route('super-admin.users.index')->with('error', 'This faculty account is already active.');
        }

        try {
            $token = Password::createToken($user);
            $user->notify(new AdminAccountSetupNotification($token));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('super-admin.users.index')->with(
                'invitation_error',
                'The invitation could not be sent. Please try again.',
            );
        }

        return redirect()->route('super-admin.users.index')->with('success', 'A new password setup link was sent to '.$user->email.'.');
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'admin') {
            return redirect()->route('super-admin.users.index')->with('error', 'Only faculty accounts can be deleted here.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('super-admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('super-admin.users.index')->with('success', 'Faculty account deleted successfully.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->role !== 'admin') {
            return redirect()->route('super-admin.users.index')->with('error', 'Only faculty accounts can be edited here.');
        }

        $validated = $request->validate([
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'course_ids' => ['prohibited'],
            'password' => ['nullable', 'string', 'confirmed', PasswordRule::min(8)->symbols(), 'regex:/[A-Z]/'],
        ]);

        $changedDepartment = isset($validated['department_id']);
        $changedPassword = filled($validated['password'] ?? null);
        $updates = [];
        if ($changedDepartment) {
            $updates['department_id'] = $validated['department_id'];
            $updates['course_id'] = null;
        }
        if ($changedPassword) {
            $updates['password'] = Hash::make($validated['password']);
        }
        if ($updates !== []) {
            $user->update($updates);
        }

        $message = $changedPassword && ! $changedDepartment
            ? 'Faculty password updated successfully.'
            : 'Faculty account updated successfully.';

        return redirect()->route('super-admin.users.index')->with('success', $message);
    }
}
