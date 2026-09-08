<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('trade_name')
                    ->label('Nombre comercial')
                    ->required(),
                TextInput::make('legal_name')
                    ->label('Razón social')
                    ->required(),
                TextInput::make('tax_id')
                    ->label('CIF/NIF')
                    ->required(),
                Select::make('company_type')
                    ->label('Tipo')
                    ->options(fn (): array => collect(CompanyType::cases())->mapWithKeys(fn (CompanyType $case) => [$case->value => $case->label()])->all())
                    ->required(),
                TextInput::make('address')
                    ->label('Dirección'),
                TextInput::make('postal_code')
                    ->label('Código postal'),
                TextInput::make('municipality')
                    ->label('Municipio'),
                TextInput::make('province')
                    ->label('Provincia'),
                TextInput::make('autonomous_community')
                    ->label('Comunidad autónoma'),
                TextInput::make('country')
                    ->label('País')
                    ->required()
                    ->default('España'),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('website')
                    ->label('Sitio web')
                    ->url(),
                TextInput::make('contact_person')
                    ->label('Persona de contacto'),
            ]);
    }
}
