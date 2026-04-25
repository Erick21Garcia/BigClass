<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Services\PermissionService;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function index()
    {
        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'created_at' => $permission->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('permissions/Index', compact('permissions'));
    }

    public function store(StorePermissionRequest $request)
    {
        $this->permissionService->create($request->validated());

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso creado exitosamente');
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ],
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $this->permissionService->update($permission, $request->validated());

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso actualizado exitosamente');
    }

    public function destroy(Permission $permission)
    {
        try {
            $this->permissionService->delete($permission);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso eliminado exitosamente');
    }
}