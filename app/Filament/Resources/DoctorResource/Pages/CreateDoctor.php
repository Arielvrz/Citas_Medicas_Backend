<?php

namespace App\Filament\Resources\DoctorResource\Pages;

use App\Filament\Resources\DoctorResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Crear el Auth User
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'medico',
        ]);
        
        // 2. Asignar rol al backend global Spatie
        $user->assignRole('medico');

        // 3. Forzar id en ForeignKey antes de guardar record de Doctor
        $data['user_id'] = $user->id;

        // 4. Limpiar array para no estallar SQL Exception
        unset($data['name'], $data['email'], $data['password']);

        return $data;
    }
}
