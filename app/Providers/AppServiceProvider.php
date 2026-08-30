<?php

namespace App\Providers;

use App\Models\GradeReport;
use App\Policies\GradeReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if ($this->app->runningInConsole() && $this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Gate::policy(GradeReport::class, GradeReportPolicy::class);
    }
}
