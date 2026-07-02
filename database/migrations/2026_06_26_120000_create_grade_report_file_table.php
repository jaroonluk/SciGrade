<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (Schema::connection('scigrad')->hasTable('grade_report_file')) {
            return;
        }

        Schema::connection('scigrad')->create('grade_report_file', function (Blueprint $table) {
            $table->increments('file_id');
            $table->unsignedInteger('grade_id');
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->dateTime('uploaded_at');
            $table->string('username', 10);
            $table->index('grade_id');
        });
    }

    public function down(): void
    {
        Schema::connection('scigrad')->dropIfExists('grade_report_file');
    }
};
