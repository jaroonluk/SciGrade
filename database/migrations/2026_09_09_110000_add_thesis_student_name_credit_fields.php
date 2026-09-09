<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('thesis_grade_student')) {
            return;
        }

        $schema->table('thesis_grade_student', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('thesis_grade_student', 'name_prefix')) {
                $table->string('name_prefix', 50)->nullable()->after('student_code');
            }
            if (! $schema->hasColumn('thesis_grade_student', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('name_prefix');
            }
            if (! $schema->hasColumn('thesis_grade_student', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
            if (! $schema->hasColumn('thesis_grade_student', 'credits_registered')) {
                $table->decimal('credits_registered', 5, 1)->nullable()->after('grade');
            }
            if (! $schema->hasColumn('thesis_grade_student', 'credits_passed')) {
                $table->decimal('credits_passed', 5, 1)->nullable()->after('credits_registered');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('thesis_grade_student')) {
            return;
        }

        $schema->table('thesis_grade_student', function (Blueprint $table) use ($schema) {
            foreach (['name_prefix', 'first_name', 'last_name', 'credits_registered', 'credits_passed'] as $col) {
                if ($schema->hasColumn('thesis_grade_student', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
