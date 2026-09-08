<?php

namespace App\Http\Controllers;

use App\Models\PdCourse;
use App\Services\GradReport2Service;
use App\Services\ThesisGrade\ThesisGradePdfParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $like = '%'.$q.'%';

        $rows = PdCourse::query()
            ->examReportable()
            ->select('subjcode', 'subjname')
            ->where(function ($query) use ($like) {
                $query->where('subjcode', 'like', $like)
                    ->orWhere('subjname', 'like', $like);
            })
            ->orderBy('subjcode')
            ->limit(50)
            ->get()
            ->unique(fn ($row) => strtoupper(trim($row->subjcode)))
            ->take(15)
            ->values()
            ->map(fn ($row) => [
                'subject_code' => trim($row->subjcode),
                'subject' => trim($row->subjname ?? ''),
            ]);

        return response()->json($rows);
    }

    public function searchThesis(Request $request, ThesisGradePdfParser $pdfParser): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $like = '%'.$q.'%';
        $exact = strtoupper(preg_replace('/\s+/', '', $q) ?? '');

        $rows = PdCourse::query()
            ->select('subjcode', 'subjname')
            ->where(function ($query) use ($like, $exact) {
                $query->where(function ($thesisQuery) use ($like) {
                    $thesisQuery->thesisOnly()
                        ->where(function ($match) use ($like) {
                            $match->where('subjcode', 'like', $like)
                                ->orWhere('subjname', 'like', $like);
                        });
                })->orWhereRaw('UPPER(TRIM(subjcode)) = ?', [$exact]);
            })
            ->orderBy('subjcode')
            ->limit(50)
            ->get()
            ->unique(fn ($row) => strtoupper(trim($row->subjcode)))
            ->take(15)
            ->values()
            ->map(function ($row) use ($pdfParser) {
                $name = trim($row->subjname ?? '');
                $choice = $pdfParser->normalizeSubjectChoice($name);

                return [
                    'subject_code' => trim($row->subjcode),
                    'subject' => $name,
                    'subject_choice' => $choice ?? '',
                ];
            })
            // คงรายการวิทยานิพนธ์ฯ หรือรหัสที่ตรงเป๊ะ (ให้ผู้ใช้เลือกได้ แม้ชื่อในฐานจะว่าง/ไม่มาตรฐาน)
            ->filter(fn (array $row) => $row['subject_choice'] !== '' || strtoupper($row['subject_code']) === $exact)
            ->values();

        return response()->json($rows);
    }

    public function jointPeers(Request $request, GradReport2Service $gradReport2): JsonResponse
    {
        $code = trim((string) $request->query('subject_code', ''));

        return response()->json($gradReport2->peersForSubject($code));
    }
}
