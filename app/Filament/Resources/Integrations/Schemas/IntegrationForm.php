<?php

namespace App\Filament\Resources\Integrations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IntegrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('provider')
                            ->options([
                                'todoist' => 'Todoist',
                                'github' => 'GitHub',
                                'slack' => 'Slack',
                            ])
                            ->native(false)
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('label')
                            ->maxLength(255)
                            ->placeholder('Optional display name'),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),

                Section::make('Credentials')
                    ->description('Stored encrypted at rest. Leave blank when editing to keep the existing token.')
                    ->schema([
                        // Virtual field, not a real model attribute -- merged into the
                        // encrypted `credentials` column via the page's mutate hooks so
                        // we never pre-fill or round-trip the decrypted secret in the form.
                        TextInput::make('token')
                            ->password()
                            ->revealable()
                            ->label('API Token / Key')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ]),
            ]);
    }
}
