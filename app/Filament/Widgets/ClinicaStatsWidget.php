<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ClinicaStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    // Configurando la columna a ocupar (ancho completo en 2 col desktop)
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $user = Auth::user();
        $isMedico = $user->hasRole('medico');
        $doctorId = $isMedico ? ($user->doctor->id ?? 0) : null;

        // Pacientes activos
        $totalPacientes = Patient::count();

        // Citas de Hoy (descartando canceladas)
        $citasHoyQuery = Appointment::whereDate('fecha', Carbon::today())
            ->where('estado', '!=', 'cancelada');
        
        if ($isMedico) {
            $citasHoyQuery->where('doctor_id', $doctorId);
        }
        $citasHoy = $citasHoyQuery->count();

        // Citas completadas el mes actual
        $citasMesQuery = Appointment::whereMonth('fecha', Carbon::now()->month)
            ->whereYear('fecha', Carbon::now()->year)
            ->where('estado', 'completada');

        if ($isMedico) {
            $citasMesQuery->where('doctor_id', $doctorId);
        }
        $citasMes = $citasMesQuery->count();

        // Médicos operando en el sistema
        $medicosActivos = Doctor::where('activo', true)->count();

        // Fecha estética de hoy
        $fechaHermosa = ucfirst(Carbon::today()->translatedFormat('l d \d\e F'));

        return [
            Stat::make('Total Pacientes', $totalPacientes)
                ->description('Pacientes registrados en el sistema')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Citas de Hoy', $citasHoy)
                ->description($fechaHermosa)
                ->color('warning')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Completadas este Mes', $citasMes)
                ->description('Historial exitoso')
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Médicos Activos', $medicosActivos)
                ->description('Personal laboral disponible')
                ->color('primary')
                ->icon('heroicon-o-user-group'),
        ];
    }
}
