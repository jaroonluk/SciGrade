<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('department_subject_pattern')) {
            return;
        }

        if (! $schema->hasColumn('department_subject_pattern', 'education_level')) {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->string('education_level', 20)->default('bachelor')->after('department_id');
            });
        }

        DB::connection('scigrad')
            ->table('department_subject_pattern')
            ->where(function ($query) {
                $query->whereNull('education_level')
                    ->orWhere('education_level', '');
            })
            ->update(['education_level' => 'bachelor']);

        try {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->dropUnique('dept_pattern_unique');
            });
        } catch (\Throwable) {
            // unique เดิมอาจไม่มีชื่อนี้
        }

        try {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->unique(['department_id', 'education_level', 'pattern'], 'dept_pattern_edu_unique');
            });
        } catch (\Throwable) {
            // มี unique นี้อยู่แล้ว
        }

        $this->copyBachelorPatternsToGraduate();
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('department_subject_pattern')) {
            return;
        }

        DB::connection('scigrad')
            ->table('department_subject_pattern')
            ->where('education_level', 'graduate')
            ->delete();

        try {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->dropUnique('dept_pattern_edu_unique');
            });
        } catch (\Throwable) {
        }

        if ($schema->hasColumn('department_subject_pattern', 'education_level')) {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->dropColumn('education_level');
            });
        }

        try {
            $schema->table('department_subject_pattern', function (Blueprint $table) {
                $table->unique(['department_id', 'pattern'], 'dept_pattern_unique');
            });
        } catch (\Throwable) {
        }
    }

    private function copyBachelorPatternsToGraduate(): void
    {
        $bachelor = DB::connection('scigrad')
            ->table('department_subject_pattern')
            ->where('education_level', 'bachelor')
            ->get();

        $now = now();
        foreach ($bachelor as $row) {
            $exists = DB::connection('scigrad')
                ->table('department_subject_pattern')
                ->where('department_id', $row->department_id)
                ->where('education_level', 'graduate')
                ->where('pattern', $row->pattern)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::connection('scigrad')->table('department_subject_pattern')->insert([
                'department_id' => $row->department_id,
                'education_level' => 'graduate',
                'pattern' => $row->pattern,
                'sort_order' => $row->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
