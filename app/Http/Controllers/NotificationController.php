<?php

namespace App\Http\Controllers;

use App\Models\StudentNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'notification' => ['sometimes', 'string', 'max:255'],
            'return_to' => ['sometimes', 'string', 'max:2048'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        $routePrefix = $this->routePrefix($user);
        $returnUrl = $this->safeReturnUrl(
            $validated['return_to'] ?? null,
            route($routePrefix.'dashboard'),
        );
        $notifications = $this->notificationQuery($user)
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $notifications->through(fn (StudentNotification|DatabaseNotification $notification): array => $this->present($notification, $routePrefix));
        $selectedNotification = isset($validated['notification'])
            ? $this->present($this->findOwnedNotification($user, $validated['notification']), $routePrefix)
            : null;
        $unreadCount = $this->unreadCount($user);

        return view('notifications.index', compact(
            'notifications',
            'selectedNotification',
            'returnUrl',
            'routePrefix',
            'unreadCount',
        ));
    }

    public function markRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $ownedNotification = $this->findOwnedNotification($user, $notification);

        if ($ownedNotification instanceof StudentNotification) {
            if ($ownedNotification->read_at === null) {
                $ownedNotification->update(['read_at' => now()]);
            }
        } else {
            $ownedNotification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => $this->unreadCount($user)]);
        }

        $routePrefix = $this->routePrefix($user);
        $returnUrl = $this->safeReturnUrl($request->input('return_to'), route($routePrefix.'dashboard'));

        if ($request->boolean('leave_page')) {
            return redirect()->to($returnUrl);
        }

        if ($request->boolean('close_detail')) {
            return redirect()->route($routePrefix.'notifications.index', ['return_to' => $returnUrl]);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'return_to' => ['sometimes', 'string', 'max:2048'],
        ]);
        $user = $request->user();
        $routePrefix = $this->routePrefix($user);

        if ($user->role === 'student') {
            $user->receivedNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        return redirect()->route(
            $routePrefix.'notifications.index',
            ['return_to' => $this->safeReturnUrl($validated['return_to'] ?? null, route($routePrefix.'dashboard'))],
        )->with('success', 'All notifications have been marked as read.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        $this->findOwnedNotification($user, $notification)->delete();

        if ($request->filled('return_to')) {
            $routePrefix = $this->routePrefix($user);
            $returnUrl = $this->safeReturnUrl($request->input('return_to'), route($routePrefix.'dashboard'));

            return redirect()->route($routePrefix.'notifications.index', ['return_to' => $returnUrl])
                ->with('success', 'Notification deleted.');
        }

        return back()->with('success', 'Notification deleted.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'student') {
            $user->receivedNotifications()->delete();
        } else {
            $user->notifications()->delete();
        }

        return back()->with('success', 'All notifications have been deleted.');
    }

    private function notificationQuery(User $user): Builder
    {
        if ($user->role === 'student') {
            return $user->receivedNotifications()->getQuery()->with('admin:id,name');
        }

        return $user->notifications()->getQuery();
    }

    private function findOwnedNotification(User $user, string $notification): StudentNotification|DatabaseNotification
    {
        return $this->notificationQuery($user)->findOrFail($notification);
    }

    /**
     * @return array{id: string, title: string, message: string, sender: string, created_at: Carbon, read: bool, is_achievement: bool, url: ?string, details: array<int, array{faculty_name: string, questions_added: int}>, read_url: string, delete_url: string}
     */
    private function present(StudentNotification|DatabaseNotification $notification, string $routePrefix): array
    {
        if ($notification instanceof StudentNotification) {
            $title = $notification->subject;
            $message = $notification->message;
            $sender = $notification->admin?->name ?? config('app.name', 'LEMU');
            $read = $notification->read_at !== null;
            $isAchievement = str_starts_with($notification->subject, 'Achievement unlocked:');
            $url = null;
            $details = [];
        } else {
            $data = $notification->data;
            $title = (string) ($data['title'] ?? 'Notification');
            $message = (string) ($data['message'] ?? '');
            if ($message === '' && isset($data['report_date'])) {
                $message = $data['report_date'].' · '.($data['total_questions_added'] ?? 0).' question(s) added';
            }
            $sender = config('app.name', 'LEMU');
            $read = $notification->read_at !== null;
            $isAchievement = false;
            $url = isset($data['url']) ? (string) $data['url'] : null;
            $details = is_array($data['faculty_counts'] ?? null) ? $data['faculty_counts'] : [];
        }

        return [
            'id' => (string) $notification->getKey(),
            'title' => $title,
            'message' => $message,
            'sender' => $sender,
            'created_at' => $notification->created_at,
            'read' => $read,
            'is_achievement' => $isAchievement,
            'url' => $url,
            'details' => $details,
            'read_url' => route($routePrefix.'notifications.read', $notification->getKey()),
            'delete_url' => route($routePrefix.'notifications.destroy', $notification->getKey()),
        ];
    }

    private function unreadCount(User $user): int
    {
        return $user->role === 'student'
            ? $user->receivedNotifications()->whereNull('read_at')->count()
            : $user->unreadNotifications()->count();
    }

    private function routePrefix(User $user): string
    {
        return match ($user->role) {
            'student' => 'student.',
            'admin' => 'admin.',
            'super-admin' => 'super-admin.',
            default => abort(404),
        };
    }

    private function safeReturnUrl(?string $returnUrl, string $fallback): string
    {
        if (
            $returnUrl !== null
            && str_starts_with($returnUrl, '/')
            && ! str_starts_with($returnUrl, '//')
            && ! str_contains($returnUrl, '\\')
            && ! preg_match('/[\r\n]/', $returnUrl)
        ) {
            return $returnUrl;
        }

        return $fallback;
    }
}
