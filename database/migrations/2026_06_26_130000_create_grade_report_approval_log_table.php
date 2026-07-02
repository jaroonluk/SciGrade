<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (Schema::connection('scigrad')->hasTable('grade_report_approval_log')) {
            return;
        }

        Schema::connection('scigrad')->create('grade_report_approval_log', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('grade_id');
            $table->string('action', 40);
            $table->integer('from_status')->nullable();
            $table->integer('to_status');
            $table->string('approver_username', 10);
            $table->string('approver_role', 30);
            $table->text('remark')->nullable();
            $table->dateTime('created_at');
            $table->index('grade_id');
            $table->index(['grade_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('scigrad')->dropIfExists('grade_report_approval_log');
    }
};
