<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RoleService;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function index()
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $role->permissions->map(fn($p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                ]),
                'created_at'  => $role->created_at->format('Y-m-d H:i'),
            ]);

        $permissions = Permission::orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'   => $p->id,
                'name' => $p->name,
            ]);

        return Inertia::render('roles/Index', compact('roles', 'permissions'));
    }

    public function store(StoreRoleRequest $request)
    {
        $this->roleService->create($request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado exitosamente');
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado exitosamente');
    }

    public function syncPermissions(Role $role)
    {
        request()->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $this->roleService->syncPermissions($role, request('permissions', []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Permisos actualizados exitosamente');
    }

    public function destroy(Role $role)
    {
        try {
            $this->roleService->delete($role);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol eliminado exitosamente');
    }
}