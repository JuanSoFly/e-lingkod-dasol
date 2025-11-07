<?php

namespace App\Providers;

use App\Contracts\DashboardServiceInterface;
use App\Services\DashboardService;
use App\Models\Employee;
use App\Observers\EmployeeObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Employee::observe(EmployeeObserver::class);

        if (app()->environment('production')) {
        // Make all generated URLs use https://
        URL::forceScheme('https');

        // Create storage link for Railway deployment
        if (app()->environment('production') && !file_exists(public_path('storage'))) {
            app('files')->link(storage_path('app/public'), public_path('storage'));
        }
    }
}
}
