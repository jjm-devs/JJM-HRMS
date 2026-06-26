<?php

namespace App\Filament\Resources\SanctionActivities\Pages;

use App\Filament\Resources\SanctionActivities\SanctionActivityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSanctionActivities extends ListRecords
{
    protected static string $resource = SanctionActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
