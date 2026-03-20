<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiamos cache de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear permisos con guard web (por defecto de Spatie)
        $permisos = [
            'ver_pacientes',
            'crear_pacientes',
            'editar_pacientes',
            'eliminar_pacientes',
            
            'ver_citas',
            'crear_citas',
            'editar_citas',
            'eliminar_citas',
            
            'ver_expedientes',
            'editar_expedientes',
            
            'gestionar_usuarios',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // 2. Crear roles y asignarles los permisos lógicos

        // ADMIN (Tiene Todo)
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        // ASISTENTE (Solo ve y edita, no elimina ni ve expedientes)
        $roleAsistente = Role::firstOrCreate(['name' => 'asistente']);
        $roleAsistente->givePermissionTo([
            'ver_pacientes',
            'crear_pacientes',
            'editar_pacientes',
            'ver_citas',
            'crear_citas',
            'editar_citas',
        ]);

        // MEDICO (Ve pacientes/citas, edita citas/expedientes)
        $roleMedico = Role::firstOrCreate(['name' => 'medico']);
        $roleMedico->givePermissionTo([
            'ver_pacientes',
            'ver_citas',
            'editar_citas',
            'ver_expedientes',
            'editar_expedientes',
        ]);
    }
}
