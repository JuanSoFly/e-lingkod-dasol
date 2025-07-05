<?php

namespace App\Providers;

use App\Models\EmployeeDocument;
use App\Policies\EmployeeDocumentPolicy;
use App\Models\LeaveApplication; 
use App\Models\PerformanceTarget;
use App\Policies\LeaveApplicationPolicy;
use App\Policies\PerformanceTargetPolicy;
use App\Models\DocumentApprovalRequest;
use App\Policies\DocumentApprovalPolicy;
use App\Models\Employee;
use App\Policies\EmployeePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        LeaveApplication::class => LeaveApplicationPolicy::class,
        PerformanceTarget::class => PerformanceTargetPolicy::class,
        EmployeeDocument::class => EmployeeDocumentPolicy::class,
        DocumentApprovalRequest::class => DocumentApprovalPolicy::class,
        Employee::class => EmployeePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // HR Analytics Gates
        Gate::define('view-analytics', function ($user) {
            return $user->can('reports.view') || 
                   $user->hasRole(['HR Admin', 'Super Admin', 'Department Head']);
        });

        Gate::define('export-analytics', function ($user) {
            return $user->can('reports.generate') || 
                   $user->hasRole(['HR Admin', 'Super Admin']);
        });

        Gate::define('manage-analytics', function ($user) {
            return $user->hasRole(['HR Admin', 'Super Admin']);
        });
    }
}