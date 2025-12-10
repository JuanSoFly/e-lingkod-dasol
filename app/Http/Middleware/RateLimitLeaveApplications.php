<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\LeaveType;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RateLimitLeaveApplications
{
    private const SICK_LEAVE_CODE = 'SL';
    private const SICK_BACKDATE_WINDOW_DAYS = 30;
    private const SICK_ADVANCE_WINDOW_DAYS = 30;

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

            // Rate limit: max 10 attempts per hour (more reasonable for production)
            $maxAttempts = 10;
            $windowMinutes = 60;

            // Basic validation before counting toward rate limit
            if (!$this->passesBasicValidation($request)) {
                // Don't count clearly invalid requests toward rate limit
                return $next($request);
            }

            if ($attempts >= $maxAttempts) {
                $ttl = Cache::get($key . ':ttl', 60);
                $retryAfter = $ttl > 0 ? $ttl : 60;

                return response()->json([
                    'message' => 'Too many leave application attempts. Please try again later.',
                    'retry_after' => $retryAfter,
                    'attempts_used' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'window_minutes' => $windowMinutes,
                ], 429)->header('Retry-After', $retryAfter);
            }

            // Increment counter
            Cache::increment($key);
            Cache::put($key . ':ttl', $windowMinutes * 60, $windowMinutes * 60);
        }

        $response = $next($request);

        // Reset rate limit counter on successful submission (2xx status)
        if ($response->isSuccessful() && str_contains($request->path(), '/leave-applications')) {
            $userId = auth()->id();
            $key = "leave_application_rate_limit:{$userId}";
            Cache::forget($key);
            Cache::forget($key . ':ttl');
        }

        return $response;
    }

    /**
     * Check if request passes basic validation before counting toward rate limit
     */
    private function passesBasicValidation(Request $request): bool
    {
        $leaveType = $request->input('leave_type_id')
            ? LeaveType::find($request->input('leave_type_id'))
            : null;
        $isSickLeave = $leaveType?->code === self::SICK_LEAVE_CODE;

        // Must have leave type ID
        if (!$request->input('leave_type_id')) {
            return false;
        }

        // Must have dates (unless it's a draft)
        $isDraft = filter_var($request->input('is_draft', false), FILTER_VALIDATE_BOOLEAN);
        if (!$isDraft && (!$request->input('start_date') || !$request->input('end_date'))) {
            return false;
        }

        // Must have reason (unless it's a draft)
        if (!$isDraft && !$request->input('reason')) {
            return false;
        }

        // Dates must be valid if provided
        if ($request->input('start_date')) {
            try {
                $startDate = Carbon::parse($request->input('start_date'));

                if ($isSickLeave) {
                    if ($startDate->lt(Carbon::now()->subDays(self::SICK_BACKDATE_WINDOW_DAYS)) ||
                        $startDate->gt(Carbon::now()->addDays(self::SICK_ADVANCE_WINDOW_DAYS))) {
                        return false; // Out of allowed sick-leave window
                    }
                } else {
                    if ($startDate->isPast()) {
                        return false; // Don't count past dates toward rate limit for non-sick leave
                    }
                }
            } catch (\Exception $e) {
                return false; // Invalid date format
            }
        }

        if ($request->input('end_date')) {
            try {
                $endDate = \Carbon\Carbon::parse($request->input('end_date'));
            } catch (\Exception $e) {
                return false; // Invalid date format
            }
        }

        return true;
    }
}
