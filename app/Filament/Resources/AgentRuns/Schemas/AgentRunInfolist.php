<?php

namespace App\Filament\Resources\AgentRuns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AgentRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('agent_id'),
                TextEntry::make('source'),
                TextEntry::make('session_id')->copyable(),
                TextEntry::make('project.name')->label('Project')->placeholder('—'),
                TextEntry::make('model'),
                TextEntry::make('model_provider'),
                TextEntry::make('input_tokens')->numeric(),
                TextEntry::make('output_tokens')->numeric(),
                TextEntry::make('total_tokens')->numeric(),
                TextEntry::make('duration_ms')->label('Duration (ms)'),
                TextEntry::make('status')->badge(),
                TextEntry::make('triggered_by'),
                TextEntry::make('started_at')->dateTime(),
                TextEntry::make('completed_at')->dateTime(),
            ]);
    }
}
