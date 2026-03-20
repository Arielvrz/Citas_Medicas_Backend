<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Exception;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int | string $record): void
    {
        if (!Auth::user()->hasRole('admin')) {
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

        return $record;
    }
}
