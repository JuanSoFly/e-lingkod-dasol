<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class OPCRSessionMiddleware
{
    /**
     * Handle an incoming request for OPCR operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request); // Let auth middleware handle unauthenticated requests
        }

        // Skip session validation for Super Admin and HR Admin
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return $next($request);
        }

        // Validate session for OPCR operations
        $this->validateOPCRSession($request, $user);

        // Track OPCR activity in session
        $this->trackOPCRActivity($request, $user);

        return $next($request);
    }

    /**
     * Validate OPCR session requirements
     */
    private function validateOPCRSession(Request $request, $user): void
    {
        $routeName = $request->route()->getName();

        // Only apply to OPCR routes
        if (!str_contains($routeName, 'opcr')) {
            return;
        }

        // Check for session hijacking indicators
        $this->validateSessionIntegrity($request, $user);

        // Check for concurrent OPCR sessions
        $this->validateConcurrentSessions($request, $user);

        // Check session timeout for sensitive operations
        $this->validateSessionTimeout($request, $user);
    }

    /**
     * Validate session integrity to prevent hijacking
     */
    private function validateSessionIntegrity(Request $request, $user): void
    {
        $currentIP = $request->ip();
        $currentUA = $request->userAgent();

        // Get stored session fingerprint
        $sessionKey = "opcr_session_fingerprint_{$user->id}";
        $storedFingerprint = Cache::get($sessionKey);

        if ($storedFingerprint) {
            // Check if IP or User Agent changed significantly
            $ipChanged = $storedFingerprint['ip'] !== $currentIP;
            $uaChanged = $this->userAgentChanged($storedFingerprint['user_agent'], $currentUA);

            if ($ipChanged || $uaChanged) {
                $this->handleSessionCompromise($request, $user, $ipChanged, $uaChanged);
            }
        } else {
            // Store initial session fingerprint
            Cache::put($sessionKey, [
                'ip' => $currentIP,
                'user_agent' => $currentUA,
                'created_at' => now(),
                'last_activity' => now(),
            ], 8 * 3600); // 8 hours
        }
    }

    /**
     * Validate concurrent OPCR sessions
     */
    private function validateConcurrentSessions(Request $request, $user): void
    {
        $currentSessionId = Session::getId();
        $concurrentKey = "opcr_concurrent_sessions_{$user->id}";

        $concurrentSessions = Cache::get($concurrentKey, []);

        // Clean up old sessions
        $concurrentSessions = array_filter($concurrentSessions, function ($session) {
            return Carbon::parse($session['last_activity'])->diffInMinutes(now()) < 30;
        });

        // Add/update current session
        $concurrentSessions[$currentSessionId] = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now()->toDateTimeString(),
            'route' => $request->route()->getName(),
        ];

        // Limit concurrent sessions to 3 per user for OPCR operations
        if (count($concurrentSessions) > 3) {
            // Remove oldest session
            $oldestSession = min($concurrentSessions, function ($a, $b) {
                return strcmp($a['last_activity'], $b['last_activity']);
            });

            unset($concurrentSessions[array_search($oldestSession, $concurrentSessions)]);

            Log::warning('OPCR concurrent session limit exceeded, removed oldest session', [
                'user_id' => $user->id,
                'removed_session' => $oldestSession,
                'remaining_sessions' => count($concurrentSessions),
            ]);
        }

        Cache::put($concurrentKey, $concurrentSessions, 3600); // 1 hour
    }

    /**
     * Validate session timeout for sensitive operations
     */
    private function validateSessionTimeout(Request $request, $user): void
    {
        $sensitiveOperations = [
            'opcr.workflows.submit',
            'opcr.workflows.approve',
            'opcr.workflows.reject',
            'opcr.ratings.store',
            'opcr.workflows.final_approve',
        ];

        $routeName = $request->route()->getName();

        if (!in_array($routeName, $sensitiveOperations)) {
            return;
        }

        $lastActivityKey = "opcr_last_activity_{$user->id}";
        $lastActivity = Cache::get($lastActivityKey);

        if ($lastActivity) {
            $minutesSinceLastActivity = Carbon::parse($lastActivity)->diffInMinutes(now());

            // Require re-authentication for sensitive operations after 15 minutes of inactivity
            if ($minutesSinceLastActivity > 15) {
                $this->handleReauthenticationRequired($request, $user);
            }
        }

        // Update last activity
        Cache::put($lastActivityKey, now()->toDateTimeString(), 3600);
    }

    /**
     * Track OPCR activity for audit trail
     */
    private function trackOPCRActivity(Request $request, $user): void
    {
        $routeName = $request->route()->getName();

        if (!str_contains($routeName, 'opcr')) {
            return;
        }

        $activityKey = "opcr_activity_{$user->id}_" . date('Y-m-d');
        $activities = Cache::get($activityKey, []);

        $activities[] = [
            'timestamp' => now()->toDateTimeString(),
            'route' => $routeName,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'parameters' => $this->sanitizeParameters($request->route()->parameters()),
        ];

        // Keep only last 100 activities per day
        if (count($activities) > 100) {
            $activities = array_slice($activities, -100);
        }

        Cache::put($activityKey, $activities, 24 * 3600); // 24 hours
    }

    /**
     * Handle session compromise detection
     */
    private function handleSessionCompromise(Request $request, $user, bool $ipChanged, bool $uaChanged): void
    {
        Log::warning('OPCR session compromise detected', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'current_ip' => $request->ip(),
            'current_ua' => $request->userAgent(),
            'ip_changed' => $ipChanged,
            'ua_changed' => $uaChanged,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Clear user's OPCR sessions
        $this->clearUserOPCRSessions($user);

        // Force logout for security
        Auth::logout();
        Session::invalidate();

        abort(403, 'Security violation detected. Please login again.');
    }

    /**
     * Handle re-authentication requirement
     */
    private function handleReauthenticationRequired(Request $request, $user): void
    {
        Log::info('OPCR re-authentication required', [
            'user_id' => $user->id,
            'route' => $request->route()->getName(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Store intended URL for post-authentication redirect
        Session::put('url.intended', $request->fullUrl());

        if ($request->expectsJson()) {
            abort(419, 'Session expired. Please refresh and try again.');
        } else {
            abort(419, 'For your security, please re-authenticate before performing this action.');
        }
    }

    /**
     * Clear all OPCR-related sessions for a user
     */
    private function clearUserOPCRSessions($user): void
    {
        $patterns = [
            "opcr_session_fingerprint_{$user->id}",
            "opcr_concurrent_sessions_{$user->id}",
            "opcr_last_activity_{$user->id}",
        ];

        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Check if user agent changed significantly
     */
    private function userAgentChanged(string $oldUA, string $newUA): bool
    {
        // Extract browser and OS information
        $oldInfo = $this->extractUserAgentInfo($oldUA);
        $newInfo = $this->extractUserAgentInfo($newUA);

        // Check for significant changes
        return $oldInfo['browser'] !== $newInfo['browser'] || $oldInfo['os'] !== $newInfo['os'];
    }

    /**
     * Extract browser and OS from user agent string
     */
    private function extractUserAgentInfo(string $userAgent): array
    {
        // Simple extraction - in production, you might want to use a dedicated library
        $browser = 'Unknown';
        $os = 'Unknown';

        // Detect browser
        if (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/[\d.]+/', $userAgent)) {
            $browser = 'Edge';
        }

        // Detect OS
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        }

        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * Sanitize route parameters for logging
     */
    private function sanitizeParameters(array $parameters): array
    {
        $sanitized = [];
        foreach ($parameters as $key => $value) {
            if (is_numeric($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = '[REDACTED]';
            }
        }
        return $sanitized;
    }

    /**
     * Get user's current OPCR session information
     */
    public static function getUserOPCRSessionInfo(int $userId): array
    {
        $sessionKey = "opcr_session_fingerprint_{$userId}";
        $concurrentKey = "opcr_concurrent_sessions_{$userId}";
        $activityKey = "opcr_activity_{$userId}_" . date('Y-m-d');

        return [
            'fingerprint' => Cache::get($sessionKey),
            'concurrent_sessions' => Cache::get($concurrentKey, []),
            'today_activities' => Cache::get($activityKey, []),
        ];
    }

    /**
     * Terminate all OPCR sessions for a user (admin function)
     */
    public static function terminateUserOPCRSessions(int $userId): bool
    {
        $patterns = [
            "opcr_session_fingerprint_{$userId}",
            "opcr_concurrent_sessions_{$userId}",
            "opcr_last_activity_{$userId}",
        ];

        foreach ($patterns as $key) {
            Cache::forget($key);
        }

        Log::warning('OPCR sessions terminated by admin', ['user_id' => $userId]);

        return true;
    }
}