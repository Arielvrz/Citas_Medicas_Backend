<?php

namespace App\Filament\Resources\DoctorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';
    
    protected static ?string $title = 'Horarios de Atención';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('dia_semana')
                    ->label('Día de la Semana')
                    ->options([
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miércoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sábado',
                        'domingo' => 'Domingo',
                    ])
                    ->required()
                    ->unique(
                        table: 'schedules', 
                        column: 'dia_semana',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, RelationManager $livewire) => $rule->where('doctor_id', $livewire->getOwnerRecord()->id)
                    )
                    ->validationMessages([
                        'unique' => 'El médico ya tiene asignado un bloque laboral para este día de la semana.',
                    ]),
                    
                Forms\Components\TimePicker::make('hora_inicio')
                    ->label('Hora de Inicio (Apertura)')
                    ->seconds(false)
                    ->required(),
                    
                Forms\Components\TimePicker::make('hora_fin')
                    ->label('Hora de Cierre')
                    ->seconds(false)
                    ->required()
                    ->after('hora_inicio')
                    ->validationMessages([
                        'after' => 'La hora final debe ser obligatoriamente posterior al horario de apertura.',
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('dia_semana')
            ->columns([
                Tables\Columns\TextColumn::make('dia_semana')
                    ->label('Día Laboral')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge(),

                Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('De')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('hora_fin')
                    ->label('Hasta')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('duracion')
                    ->label('Duración (Turno)')
                    ->getStateUsing(function ($record) {
                        $inicio = Carbon::parse($record->hora_inicio);
                        $fin = Carbon::parse($record->hora_fin);
                        return $fin->diffInHours($inicio) . ' Horas';
                    })
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                ]),
            ]);
    }
}
