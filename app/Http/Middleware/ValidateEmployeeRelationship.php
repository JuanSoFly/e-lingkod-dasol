<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmployeeRelationship
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only validate if user is authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip validation for Super Admin
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // For Employee role, ensure user has linked employee profile
        if ($user->hasRole('Employee')) {
            if (!$user->employee) {
                Log::warning('Employee role user without linked employee profile', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'ip_address' => $request->ip(),
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Redirect to employee portal with error message
                return redirect()->route('dashboard')
                    ->with('error', 'Your user account is not properly linked to an employee profile. Please contact HR administrator.');
            }
        }

        return $next($request);
    }

    /**
     * Determine if the middleware should be applied to the request
     */
    public function only($roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Check if middleware should be applied
     */
    private function shouldApply($user): bool
    {
        if (!$user) {
            return false;
        }

        if (empty($this->roles)) {
            return true;
        }

        return $user->hasAnyRole($this->roles);
    }
}
