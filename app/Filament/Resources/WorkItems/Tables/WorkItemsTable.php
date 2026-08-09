<?php

namespace App\Filament\Resources\WorkItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'done' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('assigned_agent')
                    ->badge(),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_by')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('assigned_agent')
                    ->options([
                        'ulthane' => 'Ulthane',
                        'valkyrie' => 'Valkyrie',
                        'samael' => 'Samael',
                        'raziel' => 'Raziel',
                        'morrigan' => 'Morrigan',
                        'thoth' => 'Thoth',
                        'argus' => 'Argus',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In progress',
                        'done' => 'Done',
                        'blocked' => 'Blocked',
                    ]),
                SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
