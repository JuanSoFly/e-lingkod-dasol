<?php

namespace App\Http\Controllers;

use App\Contracts\DashboardServiceInterface;
use App\Models\OfficeAssignment;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private DashboardServiceInterface $dashboardService;
    private DashboardAnalyticsService $analyticsService;

    public function __construct(
        DashboardServiceInterface $dashboardService,
        DashboardAnalyticsService $analyticsService
    ) {
        $this->dashboardService = $dashboardService;
        $this->analyticsService = $analyticsService;

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

        // Check office assignments for Department Head status BEFORE role-based redirect
        $this->syncUserRoleFromOfficeAssignments($user);

        // Reload user to get updated roles after sync
        $user->refresh();
        $user->load('roles');

        // IMPORTANT: Check office assignments directly instead of just roles
        $hasDepartmentHeadAssignment = \App\Models\OfficeAssignment::where('user_id', $user->id)
            ->where('role', \App\Models\OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->exists();

        // If user has Department Head assignment, show main dashboard, NOT employee portal
        if ($hasDepartmentHeadAssignment) {
            // Ensure user has Department Head role
            if (!$user->hasRole('Department Head')) {
                $user->assignRole('Department Head');
                if ($user->hasRole('Employee')) {
                    $user->removeRole('Employee');
                }
            }
            // Continue to main dashboard (Department Head view)
        } else {
            // Redirect Employee role users to their dedicated portal
            if ($user->hasRole('Employee') && !$user->hasAnyRole(['HR Admin', 'Super Admin'])) {
                return redirect()->route('employee-portal.dashboard');
            }
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
            
                      // Get OPCR-specific data if user has OPCR permissions
            $opcrData = [];
            if ($user->can('opcr.view') || $user->hasAnyRole(['Department Head', 'Assessor', 'Final Approver'])) {
                try {
                    $opcrData = $this->getOPCRDashboardData($user);
                } catch (\Exception $e) {
                    \Log::error('OPCR dashboard data retrieval failed', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'user_roles' => $user->getRoleNames()->toArray(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $opcrData = [];
                }
            }

            return view('dashboard', [
                // Legacy data for existing blade templates
                'totalEmployees' => $totalEmployees,
                'employeesByDept' => $employeesByDept,
                'pendingLeaveApps' => $pendingLeaveApps,
                'leaveToday' => $leaveToday,
                'upcomingBirthdays' => $upcomingBirthdays,

                // Enhanced data for future use
                'dashboardData' => $dashboardData,

                // OPCR-specific data
                'opcrData' => $opcrData,

                // User context for role-based UI rendering
                'userRole' => $user->getRoleNames()->first(),
                'canViewAllEmployees' => $user->can('employee.view') && ($user->hasRole(['HR Admin', 'Super Admin'])),
                'canApproveLeaves' => $user->can('leave.approve'),
                'canGenerateReports' => $user->can('reports.generate'),
                'canViewOPCR' => $user->can('opcr.view'),
                'canManageOPCR' => $user->can('opcr.manage'),
                'canAssessOPCR' => $user->can('opcr.assess'),
                'canApproveOPCR' => $user->can('opcr.final_approve'),
                'isDepartmentHead' => $user->hasRole('Department Head'),
                'isAssessor' => $user->hasRole('Assessor'),
                'isFinalApprover' => $user->hasRole('Final Approver'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard data retrieval failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Prevent redirect loop - check if current route is dashboard
            if (request()->routeIs('dashboard')) {
                return redirect()->route('login')->with('error', 'Dashboard temporarily unavailable. Please try again.');
            }

            // Return error view or redirect with error message
            return redirect()->back()->with('error', 'Unable to load dashboard data. Please try again later.');
        }
    }

    /**
     * Get OPCR-specific dashboard data based on user role
     */
    private function getOPCRDashboardData($user): array
    {
        $context = $this->getOPCRRoleContext($user);

        $officeScope = null;

        if ($context['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD && $context['primary_office_id']) {
            $officeScope = $context['primary_office_id'];
        } elseif (!$context['has_cross_office_access'] && $context['all_office_ids']->isNotEmpty()) {
            $officeScope = $context['all_office_ids']->first();
        }

        $baseData = [
            'metrics' => $this->analyticsService->getOPCRMetrics(null, $officeScope),
            'workflow_distribution' => $this->analyticsService->getWorkflowStateDistribution(null, $officeScope),
            'recent_activities' => $this->analyticsService->getRecentOPCRActivities(5),
        ];

        // Role-specific data
        if ($context['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD) {
            $baseData['office_comparison'] = collect($this->analyticsService->getOfficePerformanceComparison())
                ->whereIn('office_id', $context['department_head_office_ids']->toArray())
                ->values()
                ->toArray();
            $baseData['mfo_breakdown'] = $this->analyticsService->getMFOPerformanceBreakdown(null, $officeScope);
            $baseData['pending_actions'] = $this->getPendingDepartmentHeadActions($context);
        }

        if ($context['role'] === OfficeAssignment::ROLE_ASSESSOR) {
            $baseData['pending_assessments'] = $this->getPendingAssessments();
            $baseData['top_performers'] = [];
        }

        if ($context['role'] === OfficeAssignment::ROLE_FINAL_APPROVER) {
            $baseData['pending_approvals'] = $this->getPendingFinalApprovals($user);
            $baseData['office_performance'] = $this->analyticsService->getOfficePerformanceComparison();
        }

        if ($user->hasAnyRole(['HR Admin', 'Super Admin'])) {
            $baseData['office_comparison'] = $this->analyticsService->getOfficePerformanceComparison();
            $baseData['mfo_breakdown'] = $this->analyticsService->getMFOPerformanceBreakdown();
            $baseData['top_performers'] = $this->analyticsService->getTopPerformingEmployees();
            $baseData['performance_trends'] = $this->analyticsService->getPerformanceTrends();
        }

        return $baseData;
    }

    /**
     * Get pending actions for Department Head
     */
    private function getPendingDepartmentHeadActions(array $context): array
    {
        $officeIds = $context['department_head_office_ids'] ?? collect();

        if ($officeIds->isEmpty()) {
            return [];
        }

        $ids = $officeIds->toArray();

        return [
            'draft_workflows' => \App\Models\OPCRWorkflow::whereIn('office_id', $ids)
                ->where('workflow_state', 'draft')
                ->count(),
            'in_progress_workflows' => \App\Models\OPCRWorkflow::whereIn('office_id', $ids)
                ->where('workflow_state', 'in_progress')
                ->count(),
            'returned_workflows' => \App\Models\OPCRWorkflow::whereIn('office_id', $ids)
                ->where('workflow_state', 'returned')
                ->count(),
        ];
    }

    /**
     * Get pending assessments for Assessor
     */
    private function getPendingAssessments(): array
    {
        return [
            'evaluation_workflows' => \App\Models\OPCRWorkflow::where('workflow_state', 'evaluation')
                ->count(),
            'overdue_assessments' => \App\Models\OPCRWorkflow::where('workflow_state', 'evaluation')
                ->where('updated_at', '<', now()->subDays(7)) // Overdue for 7+ days
                ->count(),
        ];
    }

    /**
     * Get pending final approvals
     */
    private function getPendingFinalApprovals($user): array
    {
        return [
            'final_approval_workflows' => \App\Models\OPCRWorkflow::where('workflow_state', 'final_approval')
                ->count(),
            'critical_approvals' => \App\Models\OPCRWorkflow::where('workflow_state', 'final_approval')
                ->where('updated_at', '<', now()->subDays(3)) // Critical for 3+ days
                ->count(),
        ];
    }

    /**
     * Get OPCR performance analytics API endpoint
     */
    public function getOPCRAnalytics(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('opcr.view') && !$user->hasAnyRole(['Department Head', 'Assessor', 'Final Approver'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $periodId = $request->get('period_id');
        $officeId = $request->get('office_id');

        $context = $this->getOPCRRoleContext($user);

        if (!$officeId && $context['role'] === OfficeAssignment::ROLE_DEPARTMENT_HEAD) {
            $officeId = $context['primary_office_id'];
        }

        // Validate office access for non-admin users
        if (!$user->hasAnyRole(['HR Admin', 'Super Admin']) && $officeId) {
            if ($context['has_cross_office_access']) {
                // Cross-office roles can access all
            } elseif (!$context['all_office_ids']->contains($officeId)) {
                return response()->json(['error' => 'You can only view analytics for your assigned offices'], 403);
            }
        }

        $data = $this->analyticsService->getPerformanceSummary($periodId, $officeId);

        return response()->json($data);
    }

    /**
     * Resolve OPCR role context for dashboard operations.
     */
    private function getOPCRRoleContext($user): array
    {
        $context = [
            'role' => 'Employee',
            'department_head_office_ids' => collect(),
            'assessor_office_ids' => collect(),
            'final_approver_office_ids' => collect(),
            'all_office_ids' => collect(),
            'primary_office_id' => null,
            'has_cross_office_access' => false,
        ];

        if (!$user) {
            return $context;
        }

        if ($user->hasRole('Super Admin')) {
            $context['role'] = 'Super Admin';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        if ($user->hasRole('HR Admin')) {
            $context['role'] = 'HR Admin';
            $context['has_cross_office_access'] = true;
            return $context;
        }

        $assignments = $user->officeAssignments()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->whereIn('role', [
                OfficeAssignment::ROLE_DEPARTMENT_HEAD,
                OfficeAssignment::ROLE_ASSESSOR,
                OfficeAssignment::ROLE_FINAL_APPROVER,
            ])
            ->get();

        if ($assignments->isEmpty()) {
            return $context;
        }

        $context['department_head_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_DEPARTMENT_HEAD)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['assessor_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_ASSESSOR)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['final_approver_office_ids'] = $assignments
            ->where('role', OfficeAssignment::ROLE_FINAL_APPROVER)
            ->pluck('office_id')
            ->unique()
            ->values();

        $context['all_office_ids'] = $assignments->pluck('office_id')->unique()->values();
        $context['primary_office_id'] = $context['department_head_office_ids']->first()
            ?? $context['all_office_ids']->first();

        $context['has_cross_office_access'] = $context['assessor_office_ids']->isNotEmpty()
            || $context['final_approver_office_ids']->isNotEmpty();

        if ($context['department_head_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_DEPARTMENT_HEAD;
        } elseif ($context['assessor_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_ASSESSOR;
        } elseif ($context['final_approver_office_ids']->isNotEmpty()) {
            $context['role'] = OfficeAssignment::ROLE_FINAL_APPROVER;
        }

        return $context;
    }

    /**
     * Sync User roles from their office assignments (for role synchronization fix)
     */
    private function syncUserRoleFromOfficeAssignments($user): void
    {
        // Get active office assignments for this user
        $assignments = \App\Models\OfficeAssignment::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                      ->orWhere('ended_date', '>=', now());
            })
            ->get();

        // Check for Department Head assignment
        $hasDepartmentHeadAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_DEPARTMENT_HEAD);

        // Sync Department Head role
        if ($hasDepartmentHeadAssignment && !$user->hasRole('Department Head')) {
            $user->assignRole('Department Head');

            // Remove Employee role to avoid conflicts
            if ($user->hasRole('Employee')) {
                $user->removeRole('Employee');
            }

            Log::info('Auto-synced Department Head role from office assignment', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'synced_at' => now()->toDateTimeString(),
            ]);
        } elseif (!$hasDepartmentHeadAssignment && $user->hasRole('Department Head')) {
            // Check if user should keep Department Head role due to other assignments
            $shouldKeepRole = false;

            // Only remove if no other reason to have the role
            if (!$user->hasAnyRole(['HR Admin', 'Super Admin'])) {
                $user->removeRole('Department Head');

                // Add Employee role back if no other special roles
                if (!$user->hasAnyRole(['Assessor', 'Final Approver'])) {
                    $user->assignRole('Employee');
                }

                Log::info('Auto-removed Department Head role (no active assignment)', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'synced_at' => now()->toDateTimeString(),
                ]);
            }
        }

        // Sync Assessor role
        $hasAssessorAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_ASSESSOR);
        if ($hasAssessorAssignment && !$user->hasRole('Assessor')) {
            $user->assignRole('Assessor');
        } elseif (!$hasAssessorAssignment && $user->hasRole('Assessor')) {
            $user->removeRole('Assessor');
        }

        // Sync Final Approver role
        $hasFinalApproverAssignment = $assignments->contains('role', \App\Models\OfficeAssignment::ROLE_FINAL_APPROVER);
        if ($hasFinalApproverAssignment && !$user->hasRole('Final Approver')) {
            $user->assignRole('Final Approver');
        } elseif (!$hasFinalApproverAssignment && $user->hasRole('Final Approver')) {
            $user->removeRole('Final Approver');
        }
    }

    /**
     * Check if user can access dashboard based on roles and permissions
     */
    private function canAccessDashboard($user): bool
    {
        // Allow access if user has any of these roles
        $allowedRoles = ['Department Head', 'HR Admin', 'Super Admin', 'Assessor', 'Final Approver'];

        if ($user->hasAnyRole($allowedRoles)) {
            return true;
        }

        // Allow access if user has any dashboard-related permissions
        $dashboardPermissions = [
            'employee.view',
            'leave.view',
            'performance.view',
            'reports.view',
            'opcr.view'
        ];

        foreach ($dashboardPermissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
