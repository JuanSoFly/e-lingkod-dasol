<?php

namespace App\Providers;

use App\Contracts\DashboardServiceInterface;
use App\Contracts\DocumentApprovalServiceInterface;
use App\Services\DashboardService;
use App\Services\DocumentApprovalService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
        $this->app->bind(DocumentApprovalServiceInterface::class, DocumentApprovalService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
