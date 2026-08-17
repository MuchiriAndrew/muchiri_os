<?php

namespace App\Filament\Resources\Integrations\Pages;

use App\Filament\Resources\Integrations\IntegrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegration extends EditRecord
{
    protected static string $resource = IntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $token = $data['token'] ?? null;
        unset($data['token']);

        if ($token !== null) {
            // A new token was entered -- merge it in, preserving any other
            // credential keys this provider might already have.
            $data['credentials'] = [...($this->record->credentials ?? []), 'token' => $token];
        } else {
            // Blank means "keep the existing secret" -- don't touch the column.
            unset($data['credentials']);
        }

        return $data;
    }
}
