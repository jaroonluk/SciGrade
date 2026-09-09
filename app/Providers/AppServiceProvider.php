<?php

namespace App\Providers;

use App\Models\GradeReport;
use App\Models\ThesisGrade;
use App\Policies\GradeReportPolicy;
use App\Policies\ThesisGradePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        Gate::policy(ThesisGrade::class, ThesisGradePolicy::class);

        // ปิด connection ทันทีหลัง request เพื่อไม่ค้างบน MySQL ที่ใช้ร่วมหลายระบบ
        $this->app->terminating(function (): void {
            foreach (['scigrad', 'reg'] as $name) {
                try {
                    DB::disconnect($name);
                } catch (Throwable) {
                    // ignore
                }
            }
        });
    }
}
