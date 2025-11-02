<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OPCRRateLimitMiddleware
{
    private RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

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

        // Super Admin and HR Admin have higher limits
        $isAdmin = $user->hasAnyRole(['Super Admin', 'HR Admin']);

        // Define rate limits based on operation type and user role
        $limits = $this->getRateLimits($request, $isAdmin);

        foreach ($limits as $key => $limit) {
            if ($this->limiter->tooManyAttempts($key, $limit['max_attempts'])) {
                $this->logRateLimitExceeded($request, $key, $limit);

                return response()->json([
                    'message' => $limit['message'],
                    'retry_after' => $this->limiter->availableIn($key),
                ], 429);
            }

            $this->limiter->hit($key, $limit['decay_seconds']);
        }

        return $next($request);
    }

    /**
     * Get rate limits based on request type and user role
     */
    private function getRateLimits(Request $request, bool $isAdmin): array
    {
        $routeName = $request->route()->getName();
        $method = $request->method();
        $userId = Auth::id();
        $ipAddress = $request->ip();

        $limits = [];

        // Base limits for admin users
        if ($isAdmin) {
            $baseLimits = [
                'opcr_workflow_create' => ['max_attempts' => 50, 'decay_seconds' => 3600, 'message' => 'Too many workflow creation attempts. Please try again later.'],
                'opcr_workflow_update' => ['max_attempts' => 200, 'decay_seconds' => 3600, 'message' => 'Too many workflow update attempts. Please try again later.'],
                'opcr_rating_submit' => ['max_attempts' => 100, 'decay_seconds' => 3600, 'message' => 'Too many rating submission attempts. Please try again later.'],
                'opcr_document_upload' => ['max_attempts' => 30, 'decay_seconds' => 3600, 'message' => 'Too many document upload attempts. Please try again later.'],
                'opcr_export' => ['max_attempts' => 20, 'decay_seconds' => 3600, 'message' => 'Too many export attempts. Please try again later.'],
            ];
        } else {
            // Stricter limits for regular users
            $baseLimits = [
                'opcr_workflow_create' => ['max_attempts' => 10, 'decay_seconds' => 3600, 'message' => 'Too many workflow creation attempts. Please try again later.'],
                'opcr_workflow_update' => ['max_attempts' => 50, 'decay_seconds' => 3600, 'message' => 'Too many workflow update attempts. Please try again later.'],
                'opcr_rating_submit' => ['max_attempts' => 30, 'decay_seconds' => 3600, 'message' => 'Too many rating submission attempts. Please try again later.'],
                'opcr_document_upload' => ['max_attempts' => 15, 'decay_seconds' => 3600, 'message' => 'Too many document upload attempts. Please try again later.'],
                'opcr_export' => ['max_attempts' => 10, 'decay_seconds' => 3600, 'message' => 'Too many export attempts. Please try again later.'],
            ];
        }

        // Apply limits based on route patterns
        if (str_contains($routeName, 'opcr.workflows.store')) {
            $limits["opcr_workflow_create:{$userId}"] = $baseLimits['opcr_workflow_create'];
            $limits["opcr_workflow_create:ip:{$ipAddress}"] = ['max_attempts' => $baseLimits['opcr_workflow_create']['max_attempts'] * 2, 'decay_seconds' => 3600, 'message' => 'Too many workflow creation attempts from this IP address.'];
        }

        if (str_contains($routeName, 'opcr.workflows.update') || str_contains($routeName, 'opcr.workflows.submit')) {
            $limits["opcr_workflow_update:{$userId}"] = $baseLimits['opcr_workflow_update'];
        }

        if (str_contains($routeName, 'opcr.ratings.store') || str_contains($routeName, 'opcr.workflows.evaluate')) {
            $limits["opcr_rating_submit:{$userId}"] = $baseLimits['opcr_rating_submit'];
        }

        if (str_contains($routeName, 'opcr.documents.upload') || $method === 'POST' && str_contains($routeName, 'documents')) {
            $limits["opcr_document_upload:{$userId}"] = $baseLimits['opcr_document_upload'];
        }

        if (str_contains($routeName, 'opcr.export') || str_contains($routeName, 'export')) {
            $limits["opcr_export:{$userId}"] = $baseLimits['opcr_export'];
        }

        // Add general API rate limiting for OPCR endpoints
        if (str_contains($routeName, 'opcr') && $request->expectsJson()) {
            $limits["opcr_api:{$userId}"] = [
                'max_attempts' => $isAdmin ? 1000 : 500,
                'decay_seconds' => 3600,
                'message' => 'Too many OPCR API requests. Please try again later.'
            ];
        }

        return $limits;
    }

    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(Request $request, string $key, array $limit): void
    {
        $user = Auth::user();

        Log::warning('OPCR rate limit exceeded', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->getRoleNames()->toArray() ?? [],
            'ip_address' => $request->ip(),
            'route_name' => $request->route()->getName(),
            'method' => $request->method(),
            'limit_key' => $key,
            'message' => $limit['message'],
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toDateTimeString(),
            'request_data' => $this->sanitizeRequestData($request->all()),
        ]);
    }

    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'secret', 'key',
            'file', 'document', 'attachment', 'signature', 'approval'
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && str_contains(strtolower($key), strtolower(implode('|', $sensitiveFields)))) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = '[ARRAY]';
            } elseif ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = '[FILE: ' . $value->getClientOriginalName() . ']';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get rate limit statistics for monitoring
     */
    public static function getRateLimitStats(): array
    {
        $stats = [];

        // This would typically use Redis or cache backend to get current stats
        // For now, return basic structure

        return [
            'total_blocked_requests' => 0,
            'blocked_by_user' => [],
            'blocked_by_ip' => [],
            'most_blocked_endpoints' => [],
            'last_hour_blocks' => 0,
            'last_24h_blocks' => 0,
        ];
    }

    /**
     * Clear rate limit for a specific user (admin function)
     */
    public static function clearUserRateLimit(int $userId): bool
    {
        $limiter = app(RateLimiter::class);

        // Clear all OPCR-related rate limits for the user
        $patterns = [
            "opcr_workflow_create:{$userId}",
            "opcr_workflow_update:{$userId}",
            "opcr_rating_submit:{$userId}",
            "opcr_document_upload:{$userId}",
            "opcr_export:{$userId}",
            "opcr_api:{$userId}",
        ];

        foreach ($patterns as $key) {
            $limiter->clear($key);
        }

        Log::info('OPCR rate limits cleared for user', ['user_id' => $userId]);

        return true;
    }

    /**
     * Clear rate limit for a specific IP address (admin function)
     */
    public static function clearIPRateLimit(string $ipAddress): bool
    {
        $limiter = app(RateLimiter::class);

        // Clear IP-based rate limits
        $key = "opcr_workflow_create:ip:{$ipAddress}";
        $limiter->clear($key);

        Log::info('OPCR rate limits cleared for IP address', ['ip_address' => $ipAddress]);

        return true;
    }
}