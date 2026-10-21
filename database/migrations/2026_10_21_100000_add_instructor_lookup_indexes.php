<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('scigrad');

        if ($schema->hasTable('grade_report') && ! $this->hasIndex('grade_report', 'idx_grade_report_term_year_username')) {
            $schema->table('grade_report', function (Blueprint $table) {
                $table->index(['term', 'year', 'username'], 'idx_grade_report_term_year_username');
            });
        }

        if ($schema->hasTable('grade_std')
            && $schema->hasColumn('grade_std', 'username')
            && ! $this->hasIndex('grade_std', 'idx_grade_std_username_grade_id')) {
            $schema->table('grade_std', function (Blueprint $table) {
                $table->index(['username', 'grade_id'], 'idx_grade_std_username_grade_id');
            });
        }

        if ($schema->hasTable('grade_report_file') && ! $this->hasIndex('grade_report_file', 'idx_grade_report_file_username_grade_id')) {
            $schema->table('grade_report_file', function (Blueprint $table) {
                $table->index(['username', 'grade_id'], 'idx_grade_report_file_username_grade_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');

        if ($schema->hasTable('grade_report') && $this->hasIndex('grade_report', 'idx_grade_report_term_year_username')) {
            $schema->table('grade_report', function (Blueprint $table) {
                $table->dropIndex('idx_grade_report_term_year_username');
            });
        }

        if ($schema->hasTable('grade_std') && $this->hasIndex('grade_std', 'idx_grade_std_username_grade_id')) {
            $schema->table('grade_std', function (Blueprint $table) {
                $table->dropIndex('idx_grade_std_username_grade_id');
            });
        }

        if ($schema->hasTable('grade_report_file') && $this->hasIndex('grade_report_file', 'idx_grade_report_file_username_grade_id')) {
            $schema->table('grade_report_file', function (Blueprint $table) {
                $table->dropIndex('idx_grade_report_file_username_grade_id');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::connection('scigrad')->getConnection();
        $database = $connection->getDatabaseName();

        $count = (int) $connection->table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->count();

        return $count > 0;
    }
};
