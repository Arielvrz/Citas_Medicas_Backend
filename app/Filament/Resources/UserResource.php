<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Doctor;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administración';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre Completo')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),

                Forms\Components\CheckboxList::make('roles_array')
                    ->label('Roles de Acceso')
                    ->options(Role::all()->pluck('name', 'name'))
                    ->required()
                    ->formatStateUsing(function (?User $record) {
                        return $record ? $record->roles->pluck('name')->toArray() : [];
                    }),

                Forms\Components\Toggle::make('activo')
                    ->label('Usuario Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles Asignados')
                    ->badge()
                    ->searchable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles_filter')
                    ->relationship('roles', 'name')
                    ->label('Rol Escudo'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('toggle_activo')
                    ->label(fn(User $record) => $record->activo ? 'Desactivar' : 'Activar')
                    ->icon(fn(User $record) => $record->activo ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(User $record) => $record->activo ? 'danger' : 'success')
                    ->action(function (User $record) {
                        if (Auth::id() === $record->id) {
                            Notification::make()->title('Acción denegada')->body('No puedes desactivarte a ti mismo.')->danger()->send();
                            return;
                        }
                        $record->update(['activo' => !$record->activo]);
                    }),

                Tables\Actions\Action::make('ver_doctor')
                    ->label('Ver Doctor')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (User $record) => 
                        $record->doctor ? DoctorResource::getUrl('view', ['record' => $record->doctor->id]) : null
                    )
                    ->visible(fn (User $record) => $record->hasRole('medico') && $record->doctor !== null),

                Tables\Actions\DeleteAction::make()
                    ->action(function (User $record, Tables\Actions\DeleteAction $action) {
                        if (Auth::id() === $record->id) {
                            Notification::make()->title('Opción Inválida')->body('El administrador en sesión no puede borrarse.')->danger()->send();
                            return;
                        }
                        $record->delete();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', '!=', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasRole('admin');
    }
}
