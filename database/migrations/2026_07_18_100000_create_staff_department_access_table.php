<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (Schema::connection('scigrad')->hasTable('staff_department_access')) {
            return;
        }

        Schema::connection('scigrad')->create('staff_department_access', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 10);
            $table->unsignedInteger('department_id');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['username', 'department_id'], 'staff_dept_access_unique');
            $table->index('username');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::connection('scigrad')->dropIfExists('staff_department_access');
    }
};
