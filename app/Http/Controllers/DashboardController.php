<?php

namespace App\Http\Controllers;

use App\Contracts\DashboardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private DashboardServiceInterface $dashboardService;

    public function __construct(DashboardServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        
        // Apply authentication middleware to all methods
        $this->middleware('auth');
        
        // Apply role-based authorization middleware
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            // Check if user has any dashboard access permission
            if (!$this->canAccessDashboard($user)) {
                Log::warning('Unauthorized dashboard access attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ]);
                
                abort(403, 'You do not have permission to access the dashboard.');
            }
            
            return $next($request);
        });
    }

    public function index()
    {
        $user = Auth::user();
        
        // Redirect Employee role users to their dedicated portal
        if ($user->hasRole('Employee') && !$user->hasAnyRole(['HR Admin', 'Super Admin', 'Department Head'])) {
            return redirect()->route('employee-portal.dashboard');
        }
        
        try {
            // Get role-based dashboard data with proper authorization
            $dashboardData = $this->dashboardService->getDashboardData($user);
            
            // Log successful dashboard access
            Log::info('Dashboard accessed successfully', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames(),
            ]);
            
            // Extract legacy data structure for backward compatibility
            $totalEmployees = $dashboardData['metrics']['total_employees'] ?? 0;
            $departments = $dashboardData['department_metrics']['departments'] ?? [];
            $counts = $dashboardData['department_metrics']['counts'] ?? [];
            $employeesByDept = array_combine($departments, $counts) ?: [];
            $pendingLeaveApps = $dashboardData['metrics']['pending_leave_applications'] ?? 0;
            $leaveToday = $dashboardData['metrics']['employees_on_leave_today'] ?? 0;
            $upcomingBirthdays = $dashboardData['upcoming_birthdays'] ?? collect([]);
            
            return view('dashboard', [
                // Legacy data for existing blade templates
                'totalEmployees' => $totalEmployees,
                'employeesByDept' => $employeesByDept,
                'pendingLeaveApps' => $pendingLeaveApps,
                'leaveToday' => $leaveToday,
                'upcomingBirthdays' => $upcomingBirthdays,
                
                // Enhanced data for future use
                'dashboardData' => $dashboardData,
                
                // User context for role-based UI rendering
                'userRole' => $user->getRoleNames()->first(),
                'canViewAllEmployees' => $user->can('employee.view') && ($user->hasRole(['HR Admin', 'Super Admin'])),
                'canApproveLeaves' => $user->can('leave.approve'),
                'canGenerateReports' => $user->can('reports.generate'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard data retrieval failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return error view or redirect with error message
            return redirect()->back()->with('error', 'Unable to load dashboard data. Please try again later.');
        }
    }

    /**
     * Check if user can access dashboard based on roles and permissions
     */
    private function canAccessDashboard($user): bool
    {
        // Allow access if user has any of these roles
        $allowedRoles = ['Department Head', 'HR Admin', 'Super Admin'];
        
        if ($user->hasAnyRole($allowedRoles)) {
            return true;
        }
        
        // Allow access if user has any dashboard-related permissions
        $dashboardPermissions = [
            'employee.view',
            'leave.view', 
            'performance.view',
            'reports.view'
        ];
        
        foreach ($dashboardPermissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }
        
        return false;
    }
}