<?php

namespace App\Http\Controllers;

use App\Models\StudentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentNotificationController extends Controller
{
    public function index(): View
    {
        $student = auth()->user();
        $notifications = $student->receivedNotifications()
            ->with('admin:id,name')
            ->latest()
            ->paginate(20);
        $hasUnreadNotifications = $student->receivedNotifications()
            ->whereNull('read_at')
            ->exists();

        return view('student.notifications.index', compact('notifications', 'hasUnreadNotifications'));
    }

    public function markRead(Request $request, StudentNotification $notification): RedirectResponse|JsonResponse
    {
        $ownedNotification = auth()->user()
            ->receivedNotifications()
            ->findOrFail($notification->id);

        if ($ownedNotification->read_at === null) {
            $ownedNotification->update(['read_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => auth()->user()
                    ->receivedNotifications()
                    ->whereNull('read_at')
                    ->count(),
            ]);
        }

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        auth()->user()
            ->receivedNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications have been marked as read.');
    }

    public function destroy(StudentNotification $notification): RedirectResponse
    {
        $ownedNotification = auth()->user()
            ->receivedNotifications()
            ->findOrFail($notification->id);
        $ownedNotification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    public function destroyAll(): RedirectResponse
    {
        auth()->user()
            ->receivedNotifications()
            ->delete();

        return back()->with('success', 'All notifications have been deleted.');
    }
}
