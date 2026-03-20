<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'asistente', 'medico']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole(['admin', 'asistente'])) {
            return true;
        }

        if ($user->hasRole('medico')) {
            return $appointment->doctor?->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'asistente']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(['admin', 'asistente']);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }
}
