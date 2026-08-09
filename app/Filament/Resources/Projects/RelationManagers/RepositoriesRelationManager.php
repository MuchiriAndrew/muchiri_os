<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepositoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'repositories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('provider')
                    ->options(['github' => 'GitHub', 'gitlab' => 'GitLab', 'other' => 'Other'])
                    ->default('github')
                    ->required(),
                TextInput::make('repository')
                    ->label('org/repo')
                    ->required(),
                Select::make('role')
                    ->options(['frontend' => 'Frontend', 'backend' => 'Backend', 'infrastructure' => 'Infrastructure', 'other' => 'Other'])
                    ->default('other')
                    ->required(),
                TextInput::make('local_path'),
                TextInput::make('default_branch')
                    ->default('main'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->weight('bold'),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('provider')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('repository'),
                TextColumn::make('default_branch')
                    ->badge()
                    ->color('info'),
                TextColumn::make('local_path')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
