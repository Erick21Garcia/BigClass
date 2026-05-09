<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = Activity::with('causer')
            ->when($request->filled('subject_type'), fn ($q) =>
                $q->where('subject_type', 'like', '%' . $request->subject_type . '%')
            )
            ->when($request->filled('causer_id'), fn ($q) =>
                $q->where('causer_id', $request->causer_id)
            )
            ->when($request->filled('date_from'), fn ($q) =>
                $q->whereDate('created_at', '>=', $request->date_from)
            )
            ->when($request->filled('date_to'), fn ($q) =>
                $q->whereDate('created_at', '<=', $request->date_to)
            )
            ->orderByDesc('created_at')
            ->paginate(50)
            ->through(fn ($log) => [
                'id'           => $log->id,
                'description'  => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'subject_id'   => $log->subject_id,
                'causer'       => $log->causer ? [
                    'id'   => $log->causer->id,
                    'name' => $log->causer->name,
                ] : null,
                'properties' => [
                    'old' => $log->properties['old'] ?? null,
                    'attributes' => $log->properties['attributes'] ?? null,
                ],
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('activity-log/Index', [
            'logs'    => $logs,
            'filters' => $request->only(['subject_type', 'causer_id', 'date_from', 'date_to']),
        ]);
    }
}