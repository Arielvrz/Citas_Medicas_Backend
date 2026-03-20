<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        if (!Auth::user()->hasRole('admin')) {
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

        return $user;
    }
}
