<?php

namespace App\Filament\Resources\AgentRuns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('agent_id')
                    ->badge(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'mac' ? 'info' : 'warning'),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->placeholder('—'),
                TextColumn::make('model')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('input_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('output_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_tokens')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'error' => 'danger',
                        'timeout' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('triggered_by')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1000, 1).'s' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('agent_id')
                    ->options([
                        'ulthane' => 'Ulthane',
                        'valkyrie' => 'Valkyrie',
                        'samael' => 'Samael',
                        'raziel' => 'Raziel',
                        'morrigan' => 'Morrigan',
                        'thoth' => 'Thoth',
                        'argus' => 'Argus',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'mac' => 'Mac',
                        'vps' => 'VPS',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'ok' => 'Ok',
                        'error' => 'Error',
                        'timeout' => 'Timeout',
                        'unknown' => 'Unknown',
                    ]),
                SelectFilter::make('project_id')
                    ->relationship('project', 'name')
                    ->label('Project'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
