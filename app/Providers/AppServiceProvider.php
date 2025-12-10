<?php

namespace App\Providers;

use App\Contracts\DashboardServiceInterface;
use App\Models\Employee;
use App\Models\User;
use App\Observers\EmployeeObserver;
use App\Observers\UserObserver;
use App\Services\DashboardService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        User::observe(UserObserver::class);

        if (app()->environment('production')) {
            // Make all generated URLs use https://
            URL::forceScheme('https');

            // Create storage link for Railway deployment
            if (!file_exists(public_path('storage'))) {
                app('files')->link(storage_path('app/public'), public_path('storage'));
            }
        }
    }
}
