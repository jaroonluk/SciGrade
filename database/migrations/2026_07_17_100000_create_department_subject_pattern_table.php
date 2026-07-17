<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (! Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
            Schema::connection('scigrad')->create('department_subject_pattern', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('department_id');
                $table->string('pattern', 100);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->unique(['department_id', 'pattern'], 'dept_pattern_unique');
                $table->index(['department_id', 'sort_order']);
            });
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::connection('scigrad')->dropIfExists('department_subject_pattern');
    }

    private function seedDefaults(): void
    {
        if (! Schema::connection('scigrad')->hasTable('department_subject_pattern')) {
            return;
        }

        $existing = DB::connection('scigrad')
            ->table('department_subject_pattern')
            ->count();

        if ($existing > 0) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($this->defaultPatterns() as $departmentId => $patterns) {
            foreach (array_values($patterns) as $index => $pattern) {
                $rows[] = [
                    'department_id' => $departmentId,
                    'pattern' => $pattern,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::connection('scigrad')->table('department_subject_pattern')->insert($chunk);
        }
    }

    /**
     * @return array<int, list<string>>
     */
    private function defaultPatterns(): array
    {
        return [
            5 => ['316%', '326%', '336%', '%SC6%'],
            6 => ['312%', '313%', '343%', '332%', '%SC2%'],
            7 => ['315%', '301%', '%SC5%'],
            8 => ['311%', '331%', '%SC1%'],
            9 => ['317%', '327%', '%SC7%', 'SC7%'],
            10 => ['314%', '321%', '323%', '333%', '%SC4%'],
            11 => ['318%', '%SC8%'],
            12 => ['319%', '%SC9%'],
            17 => ['SC0%', '300%'],
            22 => ['SC0%', '300%'],
            25 => ['3007%', '302%', 'SC01%', 'SC02%', '3003%', 'SC057%', 'SC068%', 'SC069%',
                'SC002002', 'SC002003', 'SC002004', 'SC002005', 'SC002006', 'SC002007',
                'SC017891', 'SC017892', 'SC057701', 'SC057702', 'SC057703', 'SC057721',
                'SC057722', 'SC057723', 'SC057725', 'SC057729', 'SC057733', 'SC017898',
                'SC017899', 'SC057738'],
            31 => ['SC017891', 'SC017892', 'SC057701', 'SC057702', 'SC057703', 'SC057721',
                'SC057722', 'SC057723', 'SC057725', 'SC057729', 'SC057733', 'SC017898', 'SC017899'],
            34 => ['SC0%', '300%'],
            35 => ['SC027701', 'SC028891', 'SC028892', 'SC028894', 'SC028898', 'SC028899',
                'SC029990', 'SC029992', 'SC029996'],
            36 => ['300302', '300304', '300305', '300306', 'SC002001'],
        ];
    }
};
