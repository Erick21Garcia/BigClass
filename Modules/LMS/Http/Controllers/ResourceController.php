<?php

namespace Modules\LMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\LMS\Http\Requests\StoreResourceRequest;
use Modules\LMS\Models\Resource;
use Modules\LMS\Models\Unit;
use Modules\LMS\Services\ResourceService;

class ResourceController extends Controller
{
    public function __construct(
        private readonly ResourceService $resourceService
    ) {}

    public function store(StoreResourceRequest $request, Unit $unit): RedirectResponse
    {
        $this->resourceService->upload(
            $unit,
            $request->file('file'),
            $request->validated('title')
        );

        return back()->with('success', 'Material subido correctamente.');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $this->resourceService->delete($resource);

        return back()->with('success', 'Material eliminado.');
    }

    /**
     * Solo accesible vía URL firmada (Resource::downloadUrl()) — el
     * middleware 'signed' en la ruta rechaza cualquier acceso sin la firma
     * válida, así que no hace falta re-chequear permisos aquí.
     */
    public function download(Request $request, Resource $resource)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        return $this->resourceService->download($resource);
    }
}