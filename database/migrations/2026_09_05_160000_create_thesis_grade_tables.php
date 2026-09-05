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
            $schema->create('thesis_grade', function (Blueprint $table) {
                $table->increments('thesis_grade_id');
                $table->unsignedTinyInteger('term');
                $table->unsignedSmallInteger('year');
                $table->string('subject_code', 20);
                $table->string('subject', 255)->nullable();
                $table->string('section', 4);
                $table->string('course_kind', 32)->nullable();
                $table->string('username', 20);
                $table->string('teacher', 255)->nullable();
                $table->string('status', 20)->default('draft');
                $table->unsignedTinyInteger('checked_proposal')->default(0);
                $table->unsignedTinyInteger('checked_signed')->default(0);
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('received_at')->nullable();
                $table->string('received_by', 20)->nullable();
                $table->text('return_reason')->nullable();
                $table->dateTime('returned_at')->nullable();
                $table->string('returned_by', 20)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->unique(
                    ['username', 'subject_code', 'section', 'term', 'year'],
                    'uq_thesis_grade_owner_course',
                );
                $table->index(['username', 'year', 'term'], 'idx_thesis_grade_owner_term');
                $table->index(['status', 'year', 'term'], 'idx_thesis_grade_status_term');
                $table->index('subject_code', 'idx_thesis_grade_subject');
            });
        }

        if (! $schema->hasTable('thesis_grade_student')) {
            $schema->create('thesis_grade_student', function (Blueprint $table) {
                $table->increments('student_id');
                $table->unsignedInteger('thesis_grade_id');
                $table->string('student_code', 20);
                $table->string('student_name', 255)->nullable();
                $table->string('degree', 16)->default('master');
                $table->unsignedTinyInteger('thesis_terms_count')->default(1);
                $table->unsignedTinyInteger('proposal_approved')->default(0);
                $table->string('grade', 8)->nullable();
                $table->decimal('progress_credits', 5, 1)->nullable();
                $table->unsignedTinyInteger('completed')->default(0);
                $table->date('defense_date')->nullable();
                $table->string('note', 500)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->index('thesis_grade_id', 'idx_thesis_student_report');
            });
        }

        if (! $schema->hasTable('thesis_grade_file')) {
            $schema->create('thesis_grade_file', function (Blueprint $table) {
                $table->increments('file_id');
                $table->unsignedInteger('thesis_grade_id');
                $table->unsignedInteger('student_id')->nullable();
                $table->string('file_type', 32)->default('ts_report');
                $table->string('original_name', 255);
                $table->string('stored_path', 500);
                $table->dateTime('uploaded_at');
                $table->string('username', 20)->nullable();
                $table->index('thesis_grade_id', 'idx_thesis_file_report');
                $table->index(['thesis_grade_id', 'file_type'], 'idx_thesis_file_type');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('scigrad');
        $schema->dropIfExists('thesis_grade_file');
        $schema->dropIfExists('thesis_grade_student');
        $schema->dropIfExists('thesis_grade');
    }
};
