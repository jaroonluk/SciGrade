<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'scigrad';

    public function up(): void
    {
        if (! Schema::connection('scigrad')->hasTable('grade_type')) {
            return;
        }

        $db = DB::connection('scigrad');

        // LI → KKULI (สถาบันภาษา)
        $db->table('grade_type')
            ->where('nameng', 'LI')
            ->update(['nameng' => 'KKULI', 'namethai' => 'สถาบันภาษา']);

        $db->table('grade_type')
            ->where('namethai', 'สถาบันภาษา')
            ->where('nameng', '!=', 'KKULI')
            ->update(['nameng' => 'KKULI']);

        // ปรับชื่อไทยให้ตรงรายการ (รหัสเดิมถูกแล้ว)
        $thaiNameUpdates = [
            'HS' => 'มนุษยศาสตร์',
            'KKBS' => 'บริหารธุรกิจและบัญชี',
            'AR' => 'สถาปัตยกรรม',
            'VM' => 'สัตวแพทยศาสตร์',
        ];

        foreach ($thaiNameUpdates as $code => $thai) {
            $db->table('grade_type')
                ->where('nameng', $code)
                ->update(['namethai' => $thai]);
        }

        // รองรับชื่อเก่าของ VM / AR ถ้าเคยเก็บด้วยชื่อเต็มแต่รหัสอื่น
        $db->table('grade_type')
            ->whereIn('namethai', ['สัตวแพทย์ศาสตร์', 'สัตวแพทยศาสตร์'])
            ->where('nameng', '!=', 'VM')
            ->update(['nameng' => 'VM', 'namethai' => 'สัตวแพทยศาสตร์']);

        // เพิ่ม COPA ถ้ายังไม่มี
        $copaExists = $db->table('grade_type')
            ->where(function ($q) {
                $q->where('nameng', 'COPA')
                    ->orWhere('namethai', 'วิทยาลัยกิจการและนโยบายสาธารณะ');
            })
            ->exists();

        if (! $copaExists) {
            $nextId = (int) $db->table('grade_type')->max('id') + 3;
            $db->table('grade_type')->insert([
                'id' => $nextId,
                'nameng' => 'COPA',
                'namethai' => 'วิทยาลัยกิจการและนโยบายสาธารณะ',
            ]);
        } else {
            $db->table('grade_type')
                ->where('namethai', 'วิทยาลัยกิจการและนโยบายสาธารณะ')
                ->update(['nameng' => 'COPA']);
            $db->table('grade_type')
                ->where('nameng', 'COPA')
                ->update(['namethai' => 'วิทยาลัยกิจการและนโยบายสาธารณะ']);
        }

        // migrate grade_std.fac: token LI → KKULI (comma-separated)
        if (Schema::connection('scigrad')->hasTable('grade_std')) {
            $rows = $db->table('grade_std')
                ->whereNotNull('fac')
                ->where('fac', '!=', '')
                ->where(function ($q) {
                    $q->where('fac', 'LI')
                        ->orWhere('fac', 'like', 'LI,%')
                        ->orWhere('fac', 'like', '%,LI')
                        ->orWhere('fac', 'like', '%,LI,%');
                })
                ->get(['grade_std_id', 'fac']);

            foreach ($rows as $row) {
                $tokens = array_values(array_filter(array_map('trim', explode(',', (string) $row->fac))));
                $changed = false;
                foreach ($tokens as $i => $token) {
                    if ($token === 'LI') {
                        $tokens[$i] = 'KKULI';
                        $changed = true;
                    }
                }
                if ($changed) {
                    $db->table('grade_std')->where('grade_std_id', $row->grade_std_id)->update([
                        'fac' => implode(',', $tokens),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::connection('scigrad')->hasTable('grade_type')) {
            return;
        }

        $db = DB::connection('scigrad');

        $db->table('grade_type')
            ->where('nameng', 'KKULI')
            ->update(['nameng' => 'LI', 'namethai' => 'สถาบันภาษา']);

        $db->table('grade_type')
            ->where('nameng', 'HS')
            ->update(['namethai' => 'มนุษยศาสตร์และสังคมศาสตร์']);
        $db->table('grade_type')
            ->where('nameng', 'KKBS')
            ->update(['namethai' => 'บริหารธุรกิจและการบัญชี']);
        $db->table('grade_type')
            ->where('nameng', 'AR')
            ->update(['namethai' => 'สถาปัตยกรรมศาสตร์']);
        $db->table('grade_type')
            ->where('nameng', 'VM')
            ->update(['namethai' => 'สัตวแพทย์ศาสตร์']);

        $db->table('grade_type')->where('nameng', 'COPA')->delete();

        if (Schema::connection('scigrad')->hasTable('grade_std')) {
            $rows = $db->table('grade_std')
                ->whereNotNull('fac')
                ->where('fac', '!=', '')
                ->where(function ($q) {
                    $q->where('fac', 'KKULI')
                        ->orWhere('fac', 'like', 'KKULI,%')
                        ->orWhere('fac', 'like', '%,KKULI')
                        ->orWhere('fac', 'like', '%,KKULI,%');
                })
                ->get(['grade_std_id', 'fac']);

            foreach ($rows as $row) {
                $tokens = array_values(array_filter(array_map('trim', explode(',', (string) $row->fac))));
                $changed = false;
                foreach ($tokens as $i => $token) {
                    if ($token === 'KKULI') {
                        $tokens[$i] = 'LI';
                        $changed = true;
                    }
                }
                if ($changed) {
                    $db->table('grade_std')->where('grade_std_id', $row->grade_std_id)->update([
                        'fac' => implode(',', $tokens),
                    ]);
                }
            }
        }
    }
};
