<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ClinicalRecordRelationManager extends RelationManager
{
    protected static string $relationship = 'clinicalRecord';

    protected static ?string $title = 'Expediente Clínico';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tipo_sangre')
                    ->maxLength(5)
                    ->nullable(),
                Forms\Components\Textarea::make('alergias')
                    ->nullable(),
                Forms\Components\Textarea::make('enfermedades_cronicas')
                    ->nullable(),
                Forms\Components\Textarea::make('medicamentos_actuales')
                    ->nullable(),
                Forms\Components\Textarea::make('notas_medicas')
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('tipo_sangre')->label('Sangre'),
                Tables\Columns\TextColumn::make('alergias')->limit(30),
                Tables\Columns\TextColumn::make('enfermedades_cronicas')->limit(30),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn ($livewire) => ! $livewire->getOwnerRecord()->clinicalRecord()->exists()),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Auth::user()->hasRole(['admin', 'medico']);
    }

    protected function canDeleteAny(): bool
    {
        return false;
    }
}
