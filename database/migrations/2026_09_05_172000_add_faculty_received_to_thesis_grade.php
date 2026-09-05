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
        if (! $schema->hasTable('thesis_grade')) {
            return;
        }

        $schema->table('thesis_grade', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('thesis_grade', 'faculty_received_at')) {
                $table->dateTime('faculty_received_at')->nullable()->after('returned_by');
            }
            if (! $schema->hasColumn('thesis_grade', 'faculty_received_by')) {
                $table->string('faculty_received_by', 20)->nullable()->after('faculty_received_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('thesis_grade')) {
            return;
        }

        $schema->table('thesis_grade', function (Blueprint $table) use ($schema): void {
            $drops = array_values(array_filter([
                $schema->hasColumn('thesis_grade', 'faculty_received_at') ? 'faculty_received_at' : null,
                $schema->hasColumn('thesis_grade', 'faculty_received_by') ? 'faculty_received_by' : null,
            ]));
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
