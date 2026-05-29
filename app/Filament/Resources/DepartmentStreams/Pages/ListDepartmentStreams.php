<?php

namespace App\Filament\Resources\DepartmentStreams\Pages;

use App\Filament\Resources\DepartmentStreams\DepartmentStreamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDepartmentStreams extends ListRecords
{
    protected static string $resource = DepartmentStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
