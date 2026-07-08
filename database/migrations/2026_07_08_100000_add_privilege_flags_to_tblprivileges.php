<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (! Schema::connection('scigrad')->hasTable('tblprivileges')) {
            return;
        }

        Schema::connection('scigrad')->table('tblprivileges', function (Blueprint $table) {
            if (! Schema::connection('scigrad')->hasColumn('tblprivileges', 'can_print_report')) {
                $table->unsignedTinyInteger('can_print_report')->default(0)->after('level');
            }
            if (! Schema::connection('scigrad')->hasColumn('tblprivileges', 'can_view_all_instructors')) {
                $table->unsignedTinyInteger('can_view_all_instructors')->default(0)->after('can_print_report');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('scigrad')->hasTable('tblprivileges')) {
            return;
        }

        Schema::connection('scigrad')->table('tblprivileges', function (Blueprint $table) {
            if (Schema::connection('scigrad')->hasColumn('tblprivileges', 'can_view_all_instructors')) {
                $table->dropColumn('can_view_all_instructors');
            }
            if (Schema::connection('scigrad')->hasColumn('tblprivileges', 'can_print_report')) {
                $table->dropColumn('can_print_report');
            }
        });
    }
};
