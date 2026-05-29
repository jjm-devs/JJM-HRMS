<?php

namespace App\Filament\Resources\PayLevels\Pages;

use App\Filament\Resources\PayLevels\PayLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayLevels extends ListRecords
{
    protected static string $resource = PayLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
