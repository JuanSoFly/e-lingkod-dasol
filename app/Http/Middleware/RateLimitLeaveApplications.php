<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitLeaveApplications
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to leave application submission routes
        if ($request->isMethod('POST') && str_contains($request->path(), '/leave-applications')) {
            $userId = auth()->id();
            $key = "leave_application_rate_limit:{$userId}";

            // Get current attempts
            $attempts = Cache::get($key, 0);

            // Rate limit: max 3 attempts per 30 minutes
            $maxAttempts = 3;
            $windowMinutes = 30;

            if ($attempts >= $maxAttempts) {
                $ttl = Cache::get($key . ':ttl', 60);
                $retryAfter = $ttl > 0 ? $ttl : 60;

                return response()->json([
                    'message' => 'Too many leave application attempts. Please try again later.',
                    'retry_after' => $retryAfter,
                ], 429)->header('Retry-After', $retryAfter);
            }

            // Increment counter
            Cache::increment($key);
            Cache::put($key . ':ttl', $windowMinutes * 60, $windowMinutes * 60);
        }

        return $next($request);
    }
}