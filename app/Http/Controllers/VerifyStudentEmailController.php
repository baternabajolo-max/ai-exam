<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class VerifyStudentEmailController extends Controller
{
    public function verify(int $id, string $hash): RedirectResponse
    {
        $student = User::query()->findOrFail($id);

        if (! hash_equals(sha1($student->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if (! $student->hasVerifiedEmail()) {
            $student->markEmailAsVerified();
        }

        $student->forceFill(['email_verification_required' => false])->save();

        return redirect()->route('login')->with(
            'success',
            $student->status === 'approved'
                ? 'Your email is verified. You can now sign in.'
                : 'Your email is verified. Please wait for admin approval before signing in.',
        );
    }
}
