<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Models\Patient;
use App\Policies\PatientPolicy;
use App\Models\ClinicalRecord;
use App\Policies\ClinicalRecordPolicy;
use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\Doctor;
use App\Policies\DoctorPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(ClinicalRecord::class, ClinicalRecordPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Doctor::class, DoctorPolicy::class);
    }
}
