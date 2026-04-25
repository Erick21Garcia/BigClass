<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Crear un nuevo permiso
     */
    public function create(array $data): Permission
    {
        return DB::transaction(function () use ($data) {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'],
            ]);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $permission->fresh();
        });
    }

    /**
     * Actualizar un permiso existente
     */
    public function update(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data) {
            $permission->update([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'],
            ]);

            DB::table('permissions')
                ->where('id', $permission->id);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $permission->fresh();
        });
    }

    /**
     * Eliminar un permiso
     */
    public function delete(Permission $permission): bool
    {
        return DB::transaction(function () use ($permission) {
            if ($permission->roles()->count() > 0) {
                throw new \DomainException(
                    'No se puede eliminar el permiso porque está asignado a uno o más roles. ' .
                    'Primero debes removerlo de los roles.'
                );
            }

            if ($permission->users()->count() > 0) {
                throw new \DomainException(
                    'No se puede eliminar el permiso porque está asignado directamente a uno o más usuarios.'
                );
            }

            $deleted = $permission->delete();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return $deleted;
        });
    }

    /**
     * Obtener todos los permisos agrupados por recurso
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::orderBy('name')->get();

        return $permissions->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return $parts[0] ?? 'otros';
        })->toArray();
    }

    /**
     * Crear permisos en lote
     */
    public function createBatch(array $permissionsData): void
    {
        DB::transaction(function () use ($permissionsData) {
            foreach ($permissionsData as $permissionData) {
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    ['guard_name' => $permissionData['guard_name'] ?? 'web']
                );
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}