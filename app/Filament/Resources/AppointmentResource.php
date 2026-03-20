<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Patient;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión Clínica';
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::whereDate('fecha', Carbon::today());
        if (Auth::user()->hasRole('medico')) {
            $query->where('doctor_id', Auth::user()->doctor->id ?? 0);
        }
        return (string) $query->count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Cita')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship('patient', 'nombre')
                            ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->nombre} {$record->apellido} - DUI: {$record->dui}")
                            ->searchable(['nombre', 'apellido', 'dui'])
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('doctor_id')
                            ->label('Médico')
                            ->options(
                                Doctor::where('activo', true)
                                    ->with('user')
                                    ->get()
                                    ->mapWithKeys(fn ($doctor) => [$doctor->id => "{$doctor->user->name} ({$doctor->especialidad})"])
                            )
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('schedule_id', null))
                            ->required(),

                        Forms\Components\Select::make('schedule_id')
                            ->label('Turno')
                            ->options(function (Forms\Get $get) {
                                $doctorId = $get('doctor_id');
                                if (!$doctorId) return [];
                                return Schedule::where('doctor_id', $doctorId)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => ucfirst($s->dia_semana) . " ({$s->hora_inicio} a {$s->hora_fin})"]);
                            })
                            ->live()
                            ->required(),

                        Forms\Components\DatePicker::make('fecha')
                            ->label('Fecha')
                            ->minDate(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                $schedule = Schedule::find($get('schedule_id'));
                                if ($schedule && $state) {
                                    $date = Carbon::parse($state);
                                    $diasMap = [
                                        0 => 'domingo', 1 => 'lunes', 2 => 'martes',
                                        3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado'
                                    ];
                                    if ($diasMap[$date->dayOfWeek] !== $schedule->dia_semana) {
                                        Notification::make()
                                            ->title('Conflicto de Día')
                                            ->body("El médico atiende únicamente los {$schedule->dia_semana} en ese turno.")
                                            ->danger()
                                            ->send();
                                        $set('fecha', null);
                                    }
                                }
                            }),

                        Forms\Components\TimePicker::make('hora_inicio')
                            ->label('Hora Exacta')
                            ->required()
                            ->seconds(false)
                            ->rules([
                                function (Forms\Get $get, Forms\Components\Component $component) {
                                    // Obtenemos el id inyectando la url/contexto desde la vista page si fuera necesario
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $component) {
                                        $scheduleId = $get('schedule_id');
                                        $doctorId = $get('doctor_id');
                                        $fecha = $get('fecha');

                                        if (!$scheduleId || !$doctorId || !$fecha) return;

                                        $schedule = Schedule::find($scheduleId);
                                        if ($schedule) {
                                            $horaInicio = Carbon::parse($schedule->hora_inicio)->format('H:i');
                                            $horaFin = Carbon::parse($schedule->hora_fin)->format('H:i');
                                            $sel = Carbon::parse($value)->format('H:i');

                                            if ($sel < $horaInicio || $sel >= $horaFin) {
                                                $fail("La cita debe agendarse entre las {$horaInicio} y las {$horaFin}.");
                                                return;
                                            }

                                            // Extracción en edición (El livewire Component expone el record, sino será null en creación)
                                            $recordId = $component->getContainer()->getLivewire()->record?->id ?? null;

                                            $conflict = Appointment::where('doctor_id', $doctorId)
                                                ->where('fecha', $fecha)
                                                ->where('hora_inicio', 'like', $sel . '%')
                                                ->when($recordId, fn($q, $id) => $q->where('id', '!=', $id))
                                                ->exists();

                                            if ($conflict) {
                                                Notification::make()
                                                    ->title('Horario Ocupado')
                                                    ->body('El médico ya tiene una cita asignada a esa hora exacta en esa fecha.')
                                                    ->danger()
                                                    ->send();
                                                $fail('El horario exacto ya está ocupado.');
                                            }
                                        }
                                    };
                                }
                            ]),

                        Forms\Components\Select::make('estado')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'confirmada' => 'Confirmada',
                                'completada' => 'Completada',
                                'cancelada' => 'Cancelada',
                            ])
                            ->visibleOn('edit')
                            ->required(),

                        Forms\Components\Textarea::make('motivo_consulta')
                            ->label('Motivo de Consulta (Opcional)')
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.nombre_completo')
                    ->label('Paciente')
                    ->getStateUsing(fn (Appointment $record) => "{$record->patient->nombre} {$record->patient->apellido}")
                    ->searchable(['nombre', 'apellido']),

                Tables\Columns\TextColumn::make('doctor.user.name')
                    ->label('Médico')
                    ->description(fn (Appointment $record) => $record->doctor->especialidad)
                    ->searchable(['name']),

                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Hora')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'info',
                        'completada' => 'success',
                        'cancelada' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('motivo_consulta')
                    ->limit(30)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Médico')
                    ->options(Doctor::with('user')->where('activo', true)->get()->pluck('user.name', 'id'))
                    ->visible(fn () => Auth::user()->hasRole(['admin', 'asistente'])),

                Tables\Filters\SelectFilter::make('estado')
                    ->multiple()
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmada' => 'Confirmada',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                    ]),

                Tables\Filters\Filter::make('fecha')
                    ->form([
                        Forms\Components\DatePicker::make('desde'),
                        Forms\Components\DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn ($q, $date) => $q->whereDate('fecha', '>=', $date))
                            ->when($data['hasta'], fn ($q, $date) => $q->whereDate('fecha', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('citas_hoy')
                    ->label('Filtrar Citas de Hoy')
                    ->queries(
                        true: fn (Builder $query) => $query->deHoy(),
                        false: fn (Builder $query) => $query->whereDate('fecha', '!=', Carbon::today()),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => Auth::user()->hasRole(['admin', 'asistente'])),
                
                Tables\Actions\Action::make('completar')
                    ->label('Completar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['estado' => 'completada']))
                    ->visible(fn (Appointment $record) => Auth::user()->hasRole('medico') && $record->doctor?->user_id === Auth::id() && $record->estado !== 'completada'),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-backspace')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['estado' => 'cancelada']))
                    ->visible(fn (Appointment $record) => Auth::user()->hasRole(['admin', 'asistente']) && $record->estado !== 'cancelada'),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => Auth::user()->hasRole('admin')),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
        
        if (Auth::user()->hasRole('medico')) {
            $query->where('doctor_id', Auth::user()->doctor?->id ?? 0);
        }
        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasRole(['admin', 'asistente', 'medico']);
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasRole(['admin', 'asistente']);
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasRole(['admin', 'asistente']);
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->hasRole(['admin', 'asistente', 'medico']);
    }
}
