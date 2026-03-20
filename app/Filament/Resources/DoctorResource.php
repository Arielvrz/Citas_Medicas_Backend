<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorResource\Pages;
use App\Filament\Resources\DoctorResource\RelationManagers;
use App\Models\Doctor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administración';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole(['admin', 'asistente']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cuenta de Usuario')
                    ->description('Credenciales generadas para el login del especialista.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña Temporales')
                            ->password()
                            ->required()
                            ->maxLength(255),
                    ])->columns(3)
                    ->visibleOn('create'),

                Forms\Components\Section::make('Datos Profesionales')
                    ->schema([
                        Forms\Components\Select::make('especialidad')
                            ->options([
                                'Medicina General' => 'Medicina General',
                                'Pediatría' => 'Pediatría',
                                'Cardiología' => 'Cardiología',
                                'Ginecología' => 'Ginecología',
                                'Dermatología' => 'Dermatología',
                                'Neurología' => 'Neurología',
                                'Traumatología' => 'Traumatología',
                                'Psiquiatría' => 'Psiquiatría',
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('numero_colegiado')
                            ->label('Número de Colegiado')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->hint('Formato esperado: ej. MED-0000')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('telefono_consultorio')
                            ->label('Teléfono del Consultorio')
                            ->tel()
                            ->nullable()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('activo')
                            ->label('Médico Activo')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('especialidad')
                    ->label('Especialidad')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('numero_colegiado')
                    ->label('Nº Colegiado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telefono_consultorio')
                    ->label('Teléfono')
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('activo')
                    ->label('Activo')
                    ->sortable()
                    ->disabled(fn () => !Auth::user()->hasRole('admin')), // Toggle visual solo funcional para admin

                Tables\Columns\TextColumn::make('appointments_count')
                    ->counts('appointments')
                    ->label('Total Citas')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('especialidad')
                    ->options([
                        'Medicina General' => 'Medicina General',
                        'Pediatría' => 'Pediatría',
                        'Cardiología' => 'Cardiología',
                        'Ginecología' => 'Ginecología',
                        'Dermatología' => 'Dermatología',
                        'Neurología' => 'Neurología',
                        'Traumatología' => 'Traumatología',
                        'Psiquiatría' => 'Psiquiatría',
                    ]),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del Médico')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')->label('Nombre Completo')->icon('heroicon-m-user'),
                        Infolists\Components\TextEntry::make('especialidad')->icon('heroicon-m-star')->badge(),
                        Infolists\Components\TextEntry::make('numero_colegiado')->label('Nº Colegiado')->icon('heroicon-m-identification'),
                        Infolists\Components\TextEntry::make('telefono_consultorio')->label('Teléfono')->icon('heroicon-m-phone')->default('No registrado'),
                        Infolists\Components\IconEntry::make('activo')
                            ->boolean()
                            ->label('Estado de Cuenta'),
                    ])->columns(3),

                Infolists\Components\Section::make('Horarios de Atención')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('schedules')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('dia_semana')->label('Día')->badge(),
                                Infolists\Components\TextEntry::make('hora_inicio')->label('Inicio')->time('H:i'),
                                Infolists\Components\TextEntry::make('hora_fin')->label('Fin')->time('H:i'),
                            ])
                            ->columns(3)
                    ]),

                Infolists\Components\Section::make('Próximas Citas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('appointments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('fecha')->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('hora_inicio')->time('H:i'),
                                Infolists\Components\TextEntry::make('patient.nombre')->label('Paciente')->getStateUsing(fn ($record) => $record->patient->nombre . ' ' . $record->patient->apellido),
                                Infolists\Components\TextEntry::make('estado')->badge(),
                            ])
                            ->columns(4)
                            ->getStateUsing(fn (Doctor $record) => $record->appointments()->whereDate('fecha', '>=', Carbon::today())->orderBy('fecha')->orderBy('hora_inicio')->take(5)->get())
                    ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SchedulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctors::route('/'),
            'create' => Pages\CreateDoctor::route('/create'),
            'view' => Pages\ViewDoctor::route('/{record}'),
            'edit' => Pages\EditDoctor::route('/{record}/edit'),
        ];
    }
    
    // Delegation of Permissions based on requirement:
    // Solo admin puede crear, editar y eliminar médicos. Todos ven (listado e infolist).
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create', static::getModel());
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete', $record);
    }
    
    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view', $record);
    }
}
