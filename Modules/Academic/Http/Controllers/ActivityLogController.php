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
        $filters = $request->only(['subject_type', 'causer_id', 'date_from', 'date_to']);

        $logs = Activity::with('causer')
            ->when(
                filled($filters['subject_type'] ?? null),
                // Busca por el nombre corto de la clase dentro del namespace completo
                fn ($q) => $q->where('subject_type', 'like', '%\\' . $filters['subject_type'])
            )
            ->when(
                filled($filters['causer_id'] ?? null),
                fn ($q) => $q->where('causer_id', $filters['causer_id'])
            )
            ->when(
                filled($filters['date_from'] ?? null),
                fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from'])
            )
            ->when(
                filled($filters['date_to'] ?? null),
                fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to'])
            )
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($filters)   // ← preserva los filtros en los links de paginación
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
                    'old'        => $log->properties['old'] ?? null,
                    'attributes' => $log->properties['attributes'] ?? null,
                ],
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('activity-log/Index', [
            'logs'    => $logs,
            'filters' => $filters,
        ]);
    }
}