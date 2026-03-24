<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        if (! Auth::user()->hasRole('admin')) {
            Notification::make()->title('Acceso no autorizado')->danger()->send();
            $this->redirect('/admin');

            return;
        }
        parent::mount($record);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roles = $data['roles_array'] ?? [];
        unset($data['roles_array']);

        $record->update($data);

        // Sincronizar hacia tabla ModelHasRoles de Spatie
        $record->syncRoles($roles);

        if (in_array('medico', $roles) && ! $record->doctor) {
            $record->doctor()->create([
                'especialidad' => 'General (Pendiente de actualizar)',
                'numero_colegiado' => 'S/N',
                'telefono_consultorio' => 'S/N',
                'activo' => true,
            ]);
        }

        return $record;
    }
}
