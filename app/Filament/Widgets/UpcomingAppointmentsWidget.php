<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Appointment;
use App\Filament\Resources\AppointmentResource;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UpcomingAppointmentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Próximas Citas';
    protected static ?int $sort = 3;
    
    // Ocuparemos la segunda mitad restante del panel Grid 2
    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $isMedico = $user->hasRole('medico');
        
        $query = Appointment::query()
            ->whereDate('fecha', '>=', Carbon::today())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->with(['patient', 'doctor.user']);

        if ($isMedico) {
            $query->where('doctor_id', $user->doctor->id ?? 0);
        }

        return $table
            ->query(
                $query->orderBy('fecha')->orderBy('hora_inicio')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('patient.nombre_completo')
                    ->label('Paciente')
                    ->getStateUsing(fn (Appointment $record) => "{$record->patient->nombre} {$record->patient->apellido}"),
                    
                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Médico'),

                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                    
                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Hora')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'info',
                        default => 'gray',
                    }),
            ])
            ->recordUrl(
                fn (Model $record): string => AppointmentResource::getUrl('view', ['record' => $record])
            )
            ->paginated(false);
    }
}
