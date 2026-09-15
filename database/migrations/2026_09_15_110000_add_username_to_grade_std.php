<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('grade_std') || $schema->hasColumn('grade_std', 'username')) {
            return;
        }

        $schema->table('grade_std', function (Blueprint $table) {
            $table->string('username', 50)->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        if (! $schema->hasTable('grade_std') || ! $schema->hasColumn('grade_std', 'username')) {
            return;
        }

        $schema->table('grade_std', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
