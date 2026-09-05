<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('grade_report')) {
            return;
        }

        $add = array_values(array_filter([
            ! $schema->hasColumn('grade_report', 'score_s') ? 'score_s' : null,
            ! $schema->hasColumn('grade_report', 'score_u') ? 'score_u' : null,
            ! $schema->hasColumn('grade_report', 'grade_scheme') ? 'grade_scheme' : null,
        ]));

        if ($add === []) {
            return;
        }

        $schema->table('grade_report', function (Blueprint $table) use ($add) {
            if (in_array('score_s', $add, true)) {
                $table->string('score_s', 20)->nullable();
            }
            if (in_array('score_u', $add, true)) {
                $table->string('score_u', 20)->nullable();
            }
            if (in_array('grade_scheme', $add, true)) {
                $table->string('grade_scheme', 16)->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('grade_report')) {
            return;
        }

        $drops = array_values(array_filter([
            $schema->hasColumn('grade_report', 'score_s') ? 'score_s' : null,
            $schema->hasColumn('grade_report', 'score_u') ? 'score_u' : null,
            $schema->hasColumn('grade_report', 'grade_scheme') ? 'grade_scheme' : null,
        ]));

        if ($drops === []) {
            return;
        }

        $schema->table('grade_report', function (Blueprint $table) use ($drops) {
            $table->dropColumn($drops);
        });
    }
};
