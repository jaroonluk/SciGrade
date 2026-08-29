<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (! Schema::connection('scigrad')->hasTable('dept_submission')) {
            return;
        }

        if (! Schema::connection('scigrad')->hasColumn('dept_submission', 'education_level')) {
            Schema::connection('scigrad')->table('dept_submission', function (Blueprint $table) {
                $table->string('education_level', 20)->default('bachelor')->after('year');
                $table->index(['department_id', 'term', 'year', 'education_level'], 'dept_sub_edu_idx');
            });
        }

        DB::connection('scigrad')
            ->table('dept_submission')
            ->where(function ($query) {
                $query->whereNull('education_level')
                    ->orWhere('education_level', '');
            })
            ->update(['education_level' => 'bachelor']);
    }

    public function down(): void
    {
        if (! Schema::connection('scigrad')->hasTable('dept_submission')) {
            return;
        }

        if (Schema::connection('scigrad')->hasColumn('dept_submission', 'education_level')) {
            Schema::connection('scigrad')->table('dept_submission', function (Blueprint $table) {
                $table->dropIndex('dept_sub_edu_idx');
                $table->dropColumn('education_level');
            });
        }
    }
};
