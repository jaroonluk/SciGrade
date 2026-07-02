<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (! Schema::connection('scigrad')->hasTable('dept_submission')) {
            Schema::connection('scigrad')->create('dept_submission', function (Blueprint $table) {
                $table->increments('submission_id');
                $table->unsignedInteger('department_id');
                $table->unsignedTinyInteger('term');
                $table->unsignedSmallInteger('year');
                $table->string('status', 20)->default('open');
                $table->dateTime('received_at')->nullable();
                $table->string('received_by', 10)->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index(['department_id', 'term', 'year']);
                $table->index(['status', 'department_id']);
            });
        }

        if (! Schema::connection('scigrad')->hasTable('dept_submission_file')) {
            Schema::connection('scigrad')->create('dept_submission_file', function (Blueprint $table) {
                $table->increments('file_id');
                $table->unsignedInteger('submission_id');
                $table->string('original_name', 255);
                $table->string('stored_path', 500);
                $table->dateTime('uploaded_at');
                $table->string('username', 10);
                $table->index('submission_id');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('scigrad')->dropIfExists('dept_submission_file');
        Schema::connection('scigrad')->dropIfExists('dept_submission');
    }
};
