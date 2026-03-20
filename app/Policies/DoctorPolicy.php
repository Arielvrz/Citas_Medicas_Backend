<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'asistente', 'medico']);
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return $user->hasRole(['admin', 'asistente', 'medico']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Doctor $doctor): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Doctor $doctor): bool
    {
        return $user->hasRole('admin');
    }
}
