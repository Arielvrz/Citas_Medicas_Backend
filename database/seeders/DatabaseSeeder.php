<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\ClinicalRecord;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Appointment;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear roles y permisos de Spatie primero
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Crear Usuarios Base (Idempotente)

        // ADMIN
        $admin = User::firstOrCreate(['email' => 'admin@clinica.com'], [
            'name' => 'Usuario Administrador',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
        if (!$admin->hasRole('admin')) $admin->assignRole('admin');

        // ASISTENTES (2)
        for ($i = 1; $i <= 2; $i++) {
            $asistente = User::firstOrCreate(['email' => "asistente{$i}@clinica.com"], [
                'name' => "Asistente {$i}",
                'password' => bcrypt('password'),
                'role' => 'asistente'
            ]);
            if (!$asistente->hasRole('asistente')) $asistente->assignRole('asistente');
        }

        // 3. Crear 5 Médicos con Horarios Reales
        $diasLaborales = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        $turnos = [
            ['inicio' => '08:00:00', 'fin' => '11:00:00'],
            ['inicio' => '14:00:00', 'fin' => '17:00:00'],
        ];

        $doctoresGenerados = [];

        // Hacemos que esto sea idempotente leyendo si ya existen doctores
        if (Doctor::count() < 5) {
            $faltantes = 5 - Doctor::count();
            for ($i = 0; $i < $faltantes; $i++) {
                $medicoUser = User::factory()->create([
                    'role' => 'medico',
                ]);
                $medicoUser->assignRole('medico');

                $doctor = Doctor::factory()->create([
                    'user_id' => $medicoUser->id,
                ]);
                
                // Asignar entre 2 y 4 horarios distintos de la semana al doctor
                $diasAsignados = fake()->randomElements($diasLaborales, fake()->numberBetween(2, 4));
                foreach ($diasAsignados as $dia) {
                    $turnoElegido = fake()->randomElement($turnos);
                    Schedule::firstOrCreate([
                        'doctor_id' => $doctor->id,
                        'dia_semana' => $dia,
                        'hora_inicio' => $turnoElegido['inicio'],
                        'hora_fin' => $turnoElegido['fin']
                    ]);
                }
            }
        }
        
        $doctoresGenerados = Doctor::with('schedules')->get();

        // 4. Crear 20 Pacientes y sus Expedientes
        if (Patient::count() < 20) {
            $cantidadCrear = 20 - Patient::count();
            $patients = Patient::factory($cantidadCrear)->create();
            
            foreach ($patients as $paciente) {
                ClinicalRecord::factory()->create([
                    'patient_id' => $paciente->id
                ]);
            }
        }
        $pacientesRegistrados = Patient::all();

        // 5. Crear más de 20 Citas distribuidas respetando los constraints
        if (Appointment::count() < 25) {
            $citasACrear = 25 - Appointment::count();
            $fechaBase = Carbon::today()->subWeeks(4);
            $daysMap = [
                'lunes' => Carbon::MONDAY,
                'martes' => Carbon::TUESDAY,
                'miercoles' => Carbon::WEDNESDAY,
                'jueves' => Carbon::THURSDAY,
                'viernes' => Carbon::FRIDAY,
            ];

            for ($i = 0; $i < $citasACrear; $i++) {
                $doctor = $doctoresGenerados->random();
                
                // Si por alguna razón no tiene horarios, seguimos
                if ($doctor->schedules->isEmpty()) continue;
                
                $schedule = $doctor->schedules->random();
                $paciente = $pacientesRegistrados->random();
                
                // Avanzar (i) iteraciones en días para que caiga en fechas distintas (distribución de 6 semanas: -4 a +2)
                $fechaTentativa = $fechaBase->copy()->addDays($i)->next($daysMap[$schedule->dia_semana])->format('Y-m-d');
                
                $yaExiste = Appointment::where('doctor_id', $doctor->id)
                    ->where('fecha', $fechaTentativa)
                    ->where('hora_inicio', $schedule->hora_inicio)
                    ->exists();

                if (!$yaExiste) {
                    Appointment::factory()->create([
                        'patient_id' => $paciente->id,
                        'doctor_id' => $doctor->id,
                        'schedule_id' => $schedule->id,
                        'fecha' => $fechaTentativa,
                        'hora_inicio' => $schedule->hora_inicio,
                    ]);
                }
            }
        }
    }
}
