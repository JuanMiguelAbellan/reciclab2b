<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RoleName;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Déjalo en blanco para no cambiar la contraseña actual.'),
                Select::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record) => RoleName::tryFrom($record->name)?->label() ?? $record->name)
                    ->multiple()
                    ->preload()
                    // Only a superadmin can grant/revoke roles — otherwise any
                    // admin could self-promote to superadmin (the one role
                    // distinction the app actually enforces, see
                    // CompanyPolicy::delete). Disabled fields aren't
                    // dehydrated, so this can't silently strip an existing
                    // assignment when a non-superadmin saves the form.
                    ->disabled(fn (): bool => ! auth()->user()?->hasRole(RoleName::SuperAdmin->value)),
            ]);
    }
}
