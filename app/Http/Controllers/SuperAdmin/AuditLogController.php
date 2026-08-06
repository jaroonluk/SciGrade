<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuditLogEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:80'],
            'actor' => ['nullable', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = collect();
        $error = null;
        $eventOptions = AuditLogEvent::options();

        try {
            $query = AuditLog::query()
                ->with(['actor.titleRelation', 'impersonator.titleRelation'])
                ->orderByDesc('created_at')
                ->orderByDesc('log_id');

            if (! empty($filters['event'])) {
                $query->where('event', $filters['event']);
            }

            if (! empty($filters['actor'])) {
                $actor = trim($filters['actor']);
                $query->where(function ($q) use ($actor) {
                    $q->where('actor_username', 'like', '%'.$actor.'%')
                        ->orWhere('impersonator_username', 'like', '%'.$actor.'%');
                });
            }

            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', $filters['from'].' 00:00:00');
            }

            if (! empty($filters['to'])) {
                $query->where('created_at', '<=', $filters['to'].' 23:59:59');
            }

            if (! empty($filters['q'])) {
                $q = trim($filters['q']);
                $query->where(function ($builder) use ($q) {
                    $builder->where('subject_type', 'like', '%'.$q.'%')
                        ->orWhere('subject_id', 'like', '%'.$q.'%')
                        ->orWhere('request_path', 'like', '%'.$q.'%')
                        ->orWhere('ip_address', 'like', '%'.$q.'%');
                });
            }

            $logs = $query->paginate(40)->withQueryString();

            $knownEvents = AuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event');

            foreach ($knownEvents as $event) {
                if (! isset($eventOptions[$event])) {
                    $eventOptions[$event] = $event;
                }
            }
        } catch (Throwable $e) {
            $error = 'ยังไม่สามารถอ่านตาราง audit_log_scigrad ได้ กรุณาสร้างตารางตามเอกสาร docs/database/audit_log_scigrad.md';
        }

        return view('super-admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'eventOptions' => $eventOptions,
            'error' => $error,
        ]);
    }
}
