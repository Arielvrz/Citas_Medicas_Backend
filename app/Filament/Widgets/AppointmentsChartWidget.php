<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AppointmentsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Citas por Día — Últimos 14 días';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';

    // Ocupar mitad del layout configurado a nivel Provider
    protected int | string | array $columnSpan = 1;

    public ?string $filter = '14';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Últimos 7 días',
            '14' => 'Últimos 14 días',
            '30' => 'Este mes',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $user = Auth::user();
        $isMedico = $user->hasRole('medico');
        $doctorId = $isMedico ? ($user->doctor->id ?? 0) : null;

        $query = Appointment::whereBetween('fecha', [$startDate, $endDate])
            ->where('estado', '!=', 'cancelada');

        if ($isMedico) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->selectRaw('DATE(fecha) as date, count(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $period = CarbonPeriod::create($startDate, $endDate);
        
        $labels = [];
        $data = [];

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('d M');
            $data[] = $appointments->get($dateString) ?? 0;
        }

        $labelName = $isMedico ? 'Mis Citas Asignadas' : 'Total Global de Citas';

        return [
            'datasets' => [
                [
                    'label' => $labelName,
                    'data' => $data,
                    // Usando la variable color del requerimiento: azul médico
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.2)',
                    'fill' => 'origin',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
