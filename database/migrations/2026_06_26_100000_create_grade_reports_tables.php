<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->unsignedTinyInteger('term');
            $table->unsignedSmallInteger('year');
            $table->string('subject_code', 20);
            $table->string('subject_code2', 20)->nullable();
            $table->string('subject');
            $table->string('teacher')->nullable();
            $table->unsignedTinyInteger('selecttype')->default(1);
            $table->unsignedTinyInteger('degree')->nullable();
            $table->string('programid')->nullable();
            $table->unsignedTinyInteger('type_course')->default(1);
            $table->decimal('mean', 8, 2)->nullable();
            $table->decimal('sd', 8, 2)->nullable();
            $table->unsignedTinyInteger('reasonid')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedTinyInteger('statuseva')->default(2);
            $table->unsignedInteger('totalnumstdevz')->nullable();
            $table->decimal('totalevaluationscore', 4, 2)->nullable();
            $table->unsignedTinyInteger('intflag')->default(0);
            $table->string('score_a', 20)->nullable();
            $table->string('score_bb', 20)->nullable();
            $table->string('score_b', 20)->nullable();
            $table->string('score_cc', 20)->nullable();
            $table->string('score_c', 20)->nullable();
            $table->string('score_dd', 20)->nullable();
            $table->string('score_d', 20)->nullable();
            $table->string('score_f', 20)->nullable();
            $table->unsignedTinyInteger('approv')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('dateapprove2')->nullable();
            $table->timestamp('dept_approved_at')->nullable();
            $table->timestamp('faculty_approved_at')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year', 'term']);
            $table->index(['approv', 'year', 'term']);
        });

        Schema::create('grade_stds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_report_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sec')->default(1);
            $table->string('fac')->nullable();
            $table->unsignedInteger('total_std')->default(0);
            $table->unsignedInteger('num_a')->default(0);
            $table->unsignedInteger('num_bb')->default(0);
            $table->unsignedInteger('num_b')->default(0);
            $table->unsignedInteger('num_cc')->default(0);
            $table->unsignedInteger('num_c')->default(0);
            $table->unsignedInteger('num_dd')->default(0);
            $table->unsignedInteger('num_d')->default(0);
            $table->unsignedInteger('num_f')->default(0);
            $table->unsignedInteger('num_ff')->default(0);
            $table->unsignedInteger('num_i')->default(0);
            $table->unsignedInteger('num_s')->default(0);
            $table->unsignedInteger('num_v')->default(0);
            $table->unsignedInteger('num_w')->default(0);
            $table->unsignedInteger('num_out')->default(0);
            $table->decimal('evaluationscore', 4, 2)->nullable();
            $table->unsignedInteger('numstdevz')->nullable();
            $table->unsignedTinyInteger('type_course')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_stds');
        Schema::dropIfExists('grade_reports');
    }
};
