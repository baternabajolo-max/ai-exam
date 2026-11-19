<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if (in_array($user?->role, ['admin', 'super-admin'], true)) {
                $view->with([
                    'adminNavNotifications' => $user->notifications()->latest()->take(5)->get(),
                    'unreadAdminNotificationCount' => $user->unreadNotifications()->count(),
                ]);

                return;
            }

            if ($user?->role !== 'student') {
                return;
            }

            $notifications = $user->receivedNotifications()
                ->with('admin:id,name')
                ->latest()
                ->take(5)
                ->get();
            $unreadCount = $user->receivedNotifications()
                ->whereNull('read_at')
                ->count();

            $view->with([
                'studentNavNotifications' => $notifications,
                'unreadStudentNotificationCount' => $unreadCount,
            ]);
        });
    }
}
