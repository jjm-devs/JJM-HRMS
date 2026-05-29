<?php

namespace App\Filament\Resources\IntegrationSettings\Pages;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationSetting extends CreateRecord
{
    protected static string $resource = IntegrationSettingResource::class;
}
