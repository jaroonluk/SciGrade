<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\ProgramRequest;
use App\Models\TblDepartment;
use App\Models\TblProgramQa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramController extends Controller
{
    /** @var list<int|string> */
    private const EXCLUDED_DEPARTMENT_IDS = [4];

    public function index(Request $request): View
    {
        $this->repairMissingProgramIds();

        $search = trim((string) $request->input('search', ''));

        $programs = TblProgramQa::query()
            ->with('department')
            ->whereNotIn('department_id', self::EXCLUDED_DEPARTMENT_IDS)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('programname', 'like', $like)
                        ->orWhereHas('department', fn ($dept) => $dept->where('department_name', 'like', $like));
                });
            })
            ->orderBy('department_id')
            ->orderBy('typestudy')
            ->orderBy('programid')
            ->paginate(30)
            ->withQueryString();

        return view('faculty-admin.settings.programs.index', [
            'programs' => $programs,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('faculty-admin.settings.programs.form', [
            'program' => new TblProgramQa,
            'departments' => $this->allowedDepartments(),
        ]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['programid'] = (string) $this->nextProgramId();

        TblProgramQa::query()->create($data);

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'เพิ่มหลักสูตรเรียบร้อย');
    }

    public function edit(TblProgramQa $program): View
    {
        abort_if($program->programid === null || $program->programid === '', 404);

        return view('faculty-admin.settings.programs.form', [
            'program' => $program,
            'departments' => $this->allowedDepartments(),
        ]);
    }

    public function update(ProgramRequest $request, TblProgramQa $program): RedirectResponse
    {
        abort_if($program->programid === null || $program->programid === '', 404);

        $program->update($request->validated());

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'บันทึกหลักสูตรเรียบร้อย');
    }

    public function destroy(TblProgramQa $program): RedirectResponse
    {
        abort_if($program->programid === null || $program->programid === '', 404);

        $program->delete();

        return redirect()
            ->route('faculty-admin.settings.programs.index')
            ->with('status', 'ลบหลักสูตรเรียบร้อย');
    }

    private function nextProgramId(): int
    {
        $max = TblProgramQa::query()
            ->pluck('programid')
            ->map(fn (string $id) => (int) $id)
            ->max();

        return ($max ?? 0) + 1;
    }

    /**
     * แถวที่ programid ว่างทำให้สร้าง URL แก้ไข/ลบไม่ได้ — เติมรหัสให้อัตโนมัติ
     */
    private function repairMissingProgramIds(): void
    {
        $missing = DB::connection('scigrad')
            ->table('tblprogram_qa')
            ->where(function ($query) {
                $query->whereNull('programid')->orWhere('programid', '');
            })
            ->orderBy('programname')
            ->get();

        if ($missing->isEmpty()) {
            return;
        }

        $next = $this->nextProgramId();

        foreach ($missing as $row) {
            $updated = DB::connection('scigrad')
                ->table('tblprogram_qa')
                ->where('programname', $row->programname)
                ->where('department_id', $row->department_id)
                ->where('typestudy', $row->typestudy)
                ->where(function ($query) {
                    $query->whereNull('programid')->orWhere('programid', '');
                })
                ->limit(1)
                ->update(['programid' => (string) $next]);

            if ($updated > 0) {
                $next++;
            }
        }
    }

    private function allowedDepartments()
    {
        return TblDepartment::query()
            ->whereIn('department_id', TblProgramQa::ALLOWED_DEPARTMENT_IDS)
            ->orderBy('department_name')
            ->get();
    }
}
