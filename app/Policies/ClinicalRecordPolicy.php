<?php

namespace App\Policies;

use App\Models\ClinicalRecord;
use App\Models\User;

class ClinicalRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'asistente', 'medico']);
    }

    public function view(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return $user->hasRole(['admin', 'asistente', 'medico']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'medico']);
    }

    public function update(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return $user->hasRole(['admin', 'medico']);
    }

    public function delete(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return false;
    }

    public function restore(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return false;
    }

    public function forceDelete(User $user, ClinicalRecord $clinicalRecord): bool
    {
        return false;
    }
}
