<?php

namespace App\Filament\Resources\AgentRuns;

use App\Filament\Resources\AgentRuns\Pages\ListAgentRuns;
use App\Filament\Resources\AgentRuns\Pages\ViewAgentRun;
use App\Filament\Resources\AgentRuns\Schemas\AgentRunInfolist;
use App\Filament\Resources\AgentRuns\Tables\AgentRunsTable;
use App\Models\AgentRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only — records are synced in from OpenClaw session data, not created/edited here.
 */
class AgentRunResource extends Resource
{
    protected static ?string $model = AgentRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function infolist(Schema $schema): Schema
    {
        return AgentRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentRunsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentRuns::route('/'),
            'view' => ViewAgentRun::route('/{record}'),
        ];
    }
}
