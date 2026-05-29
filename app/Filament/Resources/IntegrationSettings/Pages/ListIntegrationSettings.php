<?php

namespace App\Filament\Resources\IntegrationSettings\Pages;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationSettings extends ListRecords
{
    protected static string $resource = IntegrationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
