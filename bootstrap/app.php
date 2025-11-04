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
    ])
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
