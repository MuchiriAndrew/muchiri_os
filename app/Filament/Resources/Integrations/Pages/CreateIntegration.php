<?php

namespace App\Filament\Resources\Integrations\Pages;

use App\Filament\Resources\Integrations\IntegrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegration extends CreateRecord
{
    protected static string $resource = IntegrationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $token = $data['token'] ?? null;
        unset($data['token']);

        $data['credentials'] = $token !== null ? ['token' => $token] : null;

        return $data;
    }
}
