<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar cache de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /**
         * Definición de permisos
         */
        $permissions = [
            // Gestión general
            'dashboard.view',

            // Usuarios y personas
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Roles y permisos
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Académico
            'subjects.view',
            'subjects.create',
            'subjects.edit',
            'subjects.delete',

            // Docente
            'grades.manage',
            'attendance.manage',

            // Estudiante
            'grades.view',
            'attendance.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /**
         * Roles
         */
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $docenteRole = Role::firstOrCreate(['name' => 'docente']);
        $estudianteRole = Role::firstOrCreate(['name' => 'estudiante']);

        /**
         * Permisos por rol
         */

        // ADMIN → todo
        $adminRole->syncPermissions(Permission::all());

        // DOCENTE
        $docenteRole->syncPermissions([
            'dashboard.view',

            'subjects.view',

            'grades.manage',
            'attendance.manage',
        ]);

        // ESTUDIANTE
        $estudianteRole->syncPermissions([
            'dashboard.view',

            'subjects.view',

            'grades.view',
            'attendance.view',
        ]);
    }
}
