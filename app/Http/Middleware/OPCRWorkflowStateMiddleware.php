<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\OPCRWorkflow;
use App\Models\OfficeAssignment;
use Symfony\Component\HttpFoundation\Response;

class OPCRWorkflowStateMiddleware
{
    /**
     * Handle an incoming request for OPCR workflow operations.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredState = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin can access everything
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Get workflow ID from route
        $workflowId = $request->route('opcr_workflow') ?? $request->route('workflow') ?? $request->input('workflow_id');

        if (!$workflowId) {
            return $next($request); // No workflow context, proceed
        }

        $workflow = OPCRWorkflow::findOrFail($workflowId);

        // Check if user has office-based access
        $this->validateOfficeAccess($user, $workflow, $request);

        // Check workflow state permissions
        if ($requiredState) {
            $this->validateWorkflowState($user, $workflow, $requiredState, $request);
        }

        // Add workflow to request for easy access in controllers
        $request->merge(['opcr_workflow' => $workflow]);

        return $next($request);
    }

    /**
     * Validate office-based access for OPCR workflows
     */
    private function validateOfficeAccess($user, OPCRWorkflow $workflow, Request $request): void
    {
        $hasAccess = false;
        $accessReason = '';

        // HR Admin can view all workflows
        if ($user->hasRole('HR Admin')) {
            $hasAccess = true;
            $accessReason = 'HR Admin role';
        }

        // Department Head must match workflow office
        if (!$hasAccess && $this->userHasActiveRole($user, OfficeAssignment::ROLE_DEPARTMENT_HEAD, $workflow->office_id)) {
            $hasAccess = true;
            $accessReason = 'Department Head for office';
        }

        // Assessors have cross-office visibility for evaluations
        if (!$hasAccess && $this->userHasActiveRole($user, OfficeAssignment::ROLE_ASSESSOR)) {
            $hasAccess = true;
            $accessReason = 'Assessor cross-office access';
        }

        // Final Approvers also evaluate across offices
        if (!$hasAccess && $this->userHasActiveRole($user, OfficeAssignment::ROLE_FINAL_APPROVER)) {
            $hasAccess = true;
            $accessReason = 'Final Approver cross-office access';
        }

        if (!$hasAccess) {
            Log::warning('Unauthorized OPCR workflow access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'workflow_id' => $workflow->id,
                'office_id' => $workflow->office_id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toDateTimeString()
            ]);

            abort(403, 'You do not have permission to access this OPCR workflow.');
        }

        // Log successful access for audit trail
        Log::info('OPCR workflow access granted', [
            'user_id' => $user->id,
            'workflow_id' => $workflow->id,
            'access_reason' => $accessReason,
            'ip_address' => $request->ip(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Validate workflow state permissions
     */
    private function validateWorkflowState($user, OPCRWorkflow $workflow, string $requiredState, Request $request): void
    {
        $currentState = $workflow->workflow_state;
        $userRole = $this->getUserOPCRRole($user, $workflow);

        // Define state permissions for each role
        $statePermissions = [
            'Super Admin' => [
                'draft' => ['view', 'edit', 'submit', 'delete'],
                'committed' => ['view', 'edit'],
                'in_progress' => ['view', 'edit', 'evaluate'],
                'evaluation' => ['view', 'edit', 'approve', 'reject'],
                'final_approval' => ['view', 'edit'],
                'returned' => ['view', 'edit', 'submit', 'delete'],
            ],
            'Department Head' => [
                'draft' => ['edit', 'submit', 'delete'],
                'committed' => ['view'],
                'in_progress' => ['edit', 'submit'],
                'evaluation' => ['view'],
                'final_approval' => ['view'],
                'returned' => ['view', 'edit', 'submit'],
            ],
            'Assessor' => [
                'draft' => ['view'],
                'committed' => ['view'],
                'in_progress' => ['view', 'evaluate'],
                'evaluation' => ['edit', 'submit'],
                'final_approval' => ['view'],
                'returned' => ['view', 'return'],
            ],
            'Final Approver' => [
                'draft' => ['view'],
                'committed' => ['view'],
                'in_progress' => ['view'],
                'evaluation' => ['view'],
                'final_approval' => ['edit', 'approve', 'reject'],
                'returned' => ['view', 'return'],
            ],
            'HR Admin' => [
                'draft' => ['view', 'edit', 'delete'],
                'committed' => ['view', 'edit'],
                'in_progress' => ['view', 'edit'],
                'evaluation' => ['view', 'edit'],
                'final_approval' => ['view', 'edit'],
                'returned' => ['view', 'edit', 'return'],
            ],
        ];

        // Check if user has permission for the required action in current state
        if (!isset($statePermissions[$userRole][$currentState])) {
            abort(403, "Invalid workflow state '{$currentState}' for role '{$userRole}'");
        }

        $allowedActions = $statePermissions[$userRole][$currentState];

        // Special state validation for different workflow stages
        switch ($requiredState) {
            case 'can_edit':
                if (!in_array('edit', $allowedActions)) {
                    abort(403, 'You cannot edit this OPCR in its current state.');
                }
                break;

            case 'can_submit':
                if (!in_array('submit', $allowedActions)) {
                    abort(403, 'You cannot submit this OPCR in its current state.');
                }
                break;

            case 'can_evaluate':
                if (!in_array('evaluate', $allowedActions)) {
                    abort(403, 'You cannot evaluate this OPCR in its current state.');
                }
                break;

            case 'can_approve':
                if (!in_array('approve', $allowedActions)) {
                    abort(403, 'You cannot approve this OPCR in its current state.');
                }
                break;

            case 'can_delete':
                if (!in_array('delete', $allowedActions)) {
                    abort(403, 'You cannot delete this OPCR in its current state.');
                }
                break;

            case 'can_view':
                if (!in_array('view', $allowedActions)) {
                    abort(403, 'You cannot view this OPCR in its current state.');
                }
                break;
        }
    }

    /**
     * Get user's OPCR role for a specific workflow
     */
    private function getUserOPCRRole($user, OPCRWorkflow $workflow): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'Super Admin';
        }

        if ($user->hasRole('HR Admin')) {
            return 'HR Admin';
        }

        if ($this->userHasActiveRole($user, OfficeAssignment::ROLE_DEPARTMENT_HEAD, $workflow->office_id)) {
            return OfficeAssignment::ROLE_DEPARTMENT_HEAD;
        }

        if ($this->userHasActiveRole($user, OfficeAssignment::ROLE_ASSESSOR)) {
            return OfficeAssignment::ROLE_ASSESSOR;
        }

        if ($this->userHasActiveRole($user, OfficeAssignment::ROLE_FINAL_APPROVER)) {
            return OfficeAssignment::ROLE_FINAL_APPROVER;
        }

        return 'Employee'; // Default fallback
    }

    /**
     * Determine if user holds an active OPCR role
     */
    private function userHasActiveRole($user, string $role, ?int $officeId = null): bool
    {
        $query = OfficeAssignment::where('user_id', $user->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('ended_date')
                    ->orWhere('ended_date', '>=', now());
            });

        if ($officeId !== null) {
            $query->where('office_id', $officeId);
        }

        return $query->exists();
    }

    /**
     * Create middleware instance for specific state validation
     */
    public static function requireState(string $state): self
    {
        return new self($state);
    }
}
