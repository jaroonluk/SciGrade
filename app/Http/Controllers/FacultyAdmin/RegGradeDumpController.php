<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\RegGradeDumpRequest;
use App\Services\FacultyAdmin\RegGradeDepartmentService;
use App\Services\FacultyAdmin\RegGradeDumpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class RegGradeDumpController extends Controller
{
    public function __construct(
        private readonly RegGradeDumpService $dumpService,
        private readonly RegGradeDepartmentService $regService,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return $this->redirectToManage($request->only(['term', 'year', 'department_id', 'q']))
            ->with('status', 'เมนู Download รวมเข้า「จัดการรายวิชา REG」แล้ว — ใช้ปุ่มดึงข้อมูลจาก REG ด้านบนของหน้านี้');
    }

    public function dump(RegGradeDumpRequest $request): RedirectResponse
    {
        $year = $request->integer('year');
        $term = $request->integer('term');

        try {
            $result = $this->dumpService->dump($year, $term);
        } catch (Throwable $e) {
            return $this->redirectToManage([
                'term' => $term,
                'year' => $year,
            ])
                ->withInput()
                ->withErrors(['dump' => 'ไม่สามารถดึงข้อมูลจาก REG ได้: '.$e->getMessage()]);
        }

        $status = sprintf(
            'ดึงรายวิชา REG ภาค %d/%d เรียบร้อย — พบ %d รายการ เพิ่มใหม่ %d ทับข้อมูลเดิม %d กลุ่ม ข้าม %d',
            $term,
            $year,
            $result['fetched'],
            $result['inserted'],
            $result['sections_replaced'],
            $result['skipped'],
        );

        $zeroCount = count($result['zero_enrollment']);
        if ($zeroCount > 0) {
            $status .= " — พบ {$zeroCount} กลุ่มที่ไม่มีผู้ลงทะเบียน (ENROLLSEAT = 0)";
        }

        return $this->redirectToManage([
            'term' => $term,
            'year' => $year,
        ])->with('status', $status);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->redirectToManage($request->only(['term', 'year', 'department_id', 'q', 'SEMESTER', 'ACADYEAR']))
            ->with('status', 'กรุณาเพิ่มรายวิชาจากหน้า「จัดการรายวิชา REG」');
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->redirectToManage($request->only(['term', 'year', 'department_id', 'q', 'SEMESTER', 'ACADYEAR']))
            ->with('status', 'กรุณาลบรายวิชาจากหน้า「จัดการรายวิชา REG」');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        return $this->redirectToManage($request->only(['term', 'year', 'department_id', 'q', 'SEMESTER', 'ACADYEAR']))
            ->with('status', 'กรุณาลบรายวิชาจากหน้า「จัดการรายวิชา REG」');
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function redirectToManage(array $params = []): RedirectResponse
    {
        $term = $params['term'] ?? $params['SEMESTER'] ?? null;
        $year = $params['year'] ?? $params['ACADYEAR'] ?? null;

        $query = array_filter([
            'term' => $term !== null && $term !== '' ? (int) $term : null,
            'year' => $year !== null && $year !== '' ? (int) $year : null,
            'department_id' => $params['department_id'] ?? null,
            'q' => $params['q'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->route('faculty-admin.settings.reg-grade-manage.index', $query);
    }
}
