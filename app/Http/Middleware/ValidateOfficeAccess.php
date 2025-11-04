<?php

namespace App\Http\Middleware;

use App\Models\OfficeAssignment;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateOfficeAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Allow Super Admin and HR Admin full access
        if ($user->hasAnyRole(['Super Admin', 'HR Admin'])) {
            return $next($request);
        }

        // Get office ID from request parameters
        $officeId = $this->getOfficeIdFromRequest($request);

        if (!$officeId) {
            abort(403, 'Office ID is required for access validation.');
        }

        // Check if user has active assignment for this office
        $hasAccess = OfficeAssignment::where('user_id', $user->id)
            ->where('office_id', $officeId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have permission to access this office.');
        }

        return $next($request);
    }

    /**
     * Extract office ID from various request formats
     */
    private function getOfficeIdFromRequest(Request $request): ?int
    {
        // Check route parameters
        if ($request->route('office')) {
            return $request->route('office') instanceof \App\Models\Office
                ? $request->route('office')->id
                : (int) $request->route('office');
        }

        if ($request->route('office_id')) {
            return (int) $request->route('office_id');
        }

        // Check request data
        if ($request->input('office_id')) {
            return (int) $request->input('office_id');
        }

        if ($request->input('office')) {
            return (int) $request->input('office');
        }

        return null;
    }
}