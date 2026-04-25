<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Crear un nuevo rol
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'       => $data['name'],
                'guard_name' => $data['guard_name'],
            ]);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $role->fresh();
        });
    }

    /**
     * Actualizar un rol existente
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name'       => $data['name'],
                'guard_name' => $data['guard_name'],
            ]);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $role->fresh();
        });
    }

    /**
     * Sincronizar permisos de un rol (asignar/revocar)
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        return DB::transaction(function () use ($role, $permissionIds) {
            $role->syncPermissions($permissionIds);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $role->fresh('permissions');
        });
    }

    /**
     * Eliminar un rol
     */
    public function delete(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            if ($role->users()->count() > 0) {
                throw new \DomainException(
                    'No se puede eliminar el rol porque está asignado a uno o más usuarios. ' .
                    'Primero debes removerlo de los usuarios.'
                );
            }

            $deleted = $role->delete();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $deleted;
        });
    }
}