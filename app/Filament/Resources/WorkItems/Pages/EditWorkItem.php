<?php

namespace App\Filament\Resources\WorkItems\Pages;

use App\Filament\Resources\WorkItems\WorkItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkItem extends EditRecord
{
    protected static string $resource = WorkItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
