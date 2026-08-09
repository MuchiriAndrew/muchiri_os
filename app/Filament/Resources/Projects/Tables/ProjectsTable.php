<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // dot-notation defaultSort() doesn't join relations like column-level
            // ->sortable() does — it's passed straight to orderBy(), which fails
            // because `organizations` isn't joined. Use a correlated subquery instead.
            ->defaultSort(fn (Builder $query) => $query->orderBy(
                Organization::select('priority_weight')->whereColumn('organizations.id', 'projects.organization_id'),
            ))
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Belva Digital' => 'danger',
                        'Personal' => 'gray',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'beta' => 'info',
                        'paused' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('repositories_count')
                    ->label('Repos')
                    ->counts('repositories')
                    ->badge(),
                TextColumn::make('objective')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->relationship('organization', 'name')
                    ->label('Organization'),
                SelectFilter::make('status')
                    ->options(['active' => 'Active', 'beta' => 'Beta', 'paused' => 'Paused', 'archived' => 'Archived']),
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
