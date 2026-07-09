<?php

namespace App\Providers;

use App\Models\LabReport;
use App\Policies\LabReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Klinik FM Report module — IDOR defence on every lab-report route
        // (CLAUDE.md hard safety rule #5). Explicit even though Laravel
        // auto-discovers App\Policies\LabReportPolicy by convention.
        Gate::policy(LabReport::class, LabReportPolicy::class);
    }
}
