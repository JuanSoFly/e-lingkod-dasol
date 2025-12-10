<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/health',
    )
    ->withCommands([
        \App\Console\Commands\SyncPhilippineHolidays::class,
        \App\Console\Commands\CheckIpcrHealth::class,
        \App\Console\Commands\SyncUserNamesWithEmployeeFullNames::class,
        \App\Console\Commands\CleanupOrphanedUsers::class,
        \App\Console\Commands\CheckMigrationStatus::class,
        \App\Console\Commands\CleanupArchivedAssignments::class,
        \App\Console\Commands\CascadeMissingIpcrCommand::class,
        \App\Console\Commands\LeaveAccrualCommand::class,
        \App\Console\Commands\LeaveReconcileCreditsCommand::class,
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Run accrual for the most recently completed month on the 1st day of each month
        $schedule->command('leave:accrue-monthly')->monthlyOn(1, '01:30');

        // Reconcile credits using accrual logs on the 2nd day to keep balances consistent
        $schedule->command('leave:reconcile-credits')->monthlyOn(2, '02:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureMigrations::class,
            \App\Http\Middleware\ValidateEmployeeRelationship::class,
        ]);

        // Register global middleware for Railway
        $middleware->append([
            \App\Http\Middleware\TrustProxies::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'opcr.state' => \App\Http\Middleware\OPCRWorkflowStateMiddleware::class,
            'office.access' => \App\Http\Middleware\ValidateOfficeAccess::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
