<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión Clínica';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos Personales')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('fecha_nacimiento')
                            ->required()
                            ->displayFormat('d/m/Y'),
                        Forms\Components\Select::make('genero')
                            ->options([
                                'masculino' => 'Masculino',
                                'femenino' => 'Femenino',
                                'otro' => 'Otro',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('dui')
                            ->mask('99999999-9')
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Datos de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->required()
                            ->tel(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                        Forms\Components\Textarea::make('direccion')
                            ->nullable()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre Completo')
                    ->getStateUsing(fn (Patient $record): string => "{$record->nombre} {$record->apellido}")
                    ->searchable(['nombre', 'apellido']),
                Tables\Columns\TextColumn::make('dui')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('edad')
                    ->label('Edad')
                    ->getStateUsing(fn (Patient $record) => $record->fecha_nacimiento ? $record->fecha_nacimiento->age . ' años' : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('clinical_record_exists')
                    ->label('Expediente')
                    ->boolean()
                    ->getStateUsing(fn (Patient $record): bool => $record->clinicalRecord()->exists()),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('genero')
                    ->options([
                        'masculino' => 'Masculino',
                        'femenino' => 'Femenino',
                        'otro' => 'Otro',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('registro_desde'),
                        Forms\Components\DatePicker::make('registro_hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['registro_desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['registro_hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('exportar')
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $ids = $records->pluck('id')->implode(', ');
                            Notification::make()
                                ->title('Exportación iniciada')
                                ->body("Exportando ids: $ids")
                                ->success()
                                ->send();
                        })
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información Personal')
                    ->schema([
                        Infolists\Components\TextEntry::make('nombre')->icon('heroicon-m-user'),
                        Infolists\Components\TextEntry::make('apellido')->icon('heroicon-m-user'),
                        Infolists\Components\TextEntry::make('fecha_nacimiento')->date()->icon('heroicon-m-calendar'),
                        Infolists\Components\TextEntry::make('genero')->icon('heroicon-m-users'),
                        Infolists\Components\TextEntry::make('dui')->icon('heroicon-m-identification'),
                        Infolists\Components\TextEntry::make('telefono')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('email')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('direccion')->icon('heroicon-m-map-pin')->columnSpanFull(),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('Expediente Clínico')
                    ->schema([
                        Infolists\Components\TextEntry::make('clinicalRecord.tipo_sangre')
                            ->label('Tipo de Sangre')
                            ->default('No especificado'),
                        Infolists\Components\TextEntry::make('clinicalRecord.alergias')
                            ->label('Alergias')
                            ->default('Ninguna reportada'),
                        Infolists\Components\TextEntry::make('clinicalRecord.enfermedades_cronicas')
                            ->label('Enfermedades Crónicas')
                            ->default('Ninguna reportada'),
                        Infolists\Components\TextEntry::make('clinicalRecord.medicamentos_actuales')
                            ->label('Medicamentos Actuales')
                            ->default('Ninguno'),
                        Infolists\Components\TextEntry::make('clinicalRecord.notas_medicas')
                            ->label('Notas')
                            ->default('')
                            ->columnSpanFull(),
                    ])->columns(2)
                    ->description(fn (Patient $record) => $record->clinicalRecord ? '' : 'Sin expediente registrado'),
                    
                Infolists\Components\Section::make('Historial de Citas (Últimas 5)')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('appointments')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('fecha')->date(),
                                Infolists\Components\TextEntry::make('doctor.user.name')->label('Médico'),
                                Infolists\Components\TextEntry::make('estado')->badge(),
                            ])
                            ->columns(3)
                            ->getStateUsing(fn (Patient $record) => $record->appointments()->latest('fecha')->take(5)->get())
                    ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ClinicalRecordRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    
    public static function canViewAny(): bool
    {
        return Auth::user()->can('viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create', static::getModel());
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->can('update', $record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->can('delete', $record);
    }
    
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->can('view', $record);
    }
}
