<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackFacultyActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $faculty = $request->user();
        if ($faculty?->role === 'admin'
            && ($faculty->last_active_at === null || $faculty->last_active_at->lt(now()->subMinutes(5)))) {
            $faculty->forceFill(['last_active_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
