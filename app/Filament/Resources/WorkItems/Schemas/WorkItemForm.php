<?php

namespace App\Filament\Resources\WorkItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WorkItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('assigned_agent')
                    ->options([
            'ulthane' => 'Ulthane',
            'valkyrie' => 'Valkyrie',
            'samael' => 'Samael',
            'raziel' => 'Raziel',
            'morrigan' => 'Morrigan',
            'thoth' => 'Thoth',
            'argus' => 'Argus',
        ])
                    ->required(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'done' => 'Done',
            'blocked' => 'Blocked',
        ])
                    ->default('pending')
                    ->required(),
                TextInput::make('created_by')
                    ->required(),
                Textarea::make('result')
                    ->columnSpanFull(),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
