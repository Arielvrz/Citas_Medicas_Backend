<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        if (! Auth::user()->hasRole('admin')) {
            Notification::make()->title('Acceso no autorizado')->danger()->send();
            $this->redirect('/admin');

            return;
        }
        parent::mount();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $roles = $data['roles_array'] ?? [];
        unset($data['roles_array']);

        $user = static::getModel()::create($data);
        $user->syncRoles($roles);

        if (in_array('medico', $roles)) {
            $user->doctor()->create([
                'especialidad' => 'General (Pendiente de actualizar)',
                'numero_colegiado' => 'S/N',
                'telefono_consultorio' => 'S/N',
                'activo' => true,
            ]);
        }

        return $user;
    }
}
