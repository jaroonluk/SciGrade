<?php

namespace App\Http\Controllers;

use App\Models\PdCourse;
use App\Services\GradReport2Service;
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

    public function jointPeers(Request $request, GradReport2Service $gradReport2): JsonResponse
    {
        $code = trim((string) $request->query('subject_code', ''));

        return response()->json($gradReport2->peersForSubject($code));
    }
}
