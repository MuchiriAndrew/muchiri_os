<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('kind')
                    ->options(['employer' => 'Employer', 'client' => 'Client', 'personal' => 'Personal'])
                    ->default('client')
                    ->required(),
                TextInput::make('priority_weight')
                    ->required()
                    ->numeric()
                    ->default(9),
            ]);
    }
}
