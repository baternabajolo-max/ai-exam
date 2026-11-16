<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $profileData = [];

        if ($request->user()->role === 'student') {
            $profileData += $request->safe()->only(['badge_icon']);
        }

        if ($request->filled('first_name') || $request->filled('last_name')) {
            $profileData['name'] = trim($request->string('first_name')->toString().' '.$request->string('last_name')->toString());
        }

        foreach ([
            'profile_picture' => 'profile_picture_path',
            'background_picture' => 'background_picture_path',
        ] as $uploadField => $profileField) {
            if ($request->hasFile($uploadField)) {
                $oldPath = $request->user()->{$profileField};
                $profileData[$profileField] = $request->file($uploadField)->store('profile-images', 'public');

                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $request->user()->update($profileData);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        if (Hash::check($request->string('password')->toString(), $request->user()->password)) {
            return back()->withErrors(['password' => 'Choose a password you have not used for this account.']);
        }

        $request->user()->update(['password' => Hash::make($request->string('password')->toString())]);

        return back()->with('success', 'Password changed successfully.');
    }
}
