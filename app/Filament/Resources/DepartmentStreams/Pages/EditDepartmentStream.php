<?php

namespace App\Filament\Resources\DepartmentStreams\Pages;

use App\Filament\Resources\DepartmentStreams\DepartmentStreamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDepartmentStream extends EditRecord
{
    protected static string $resource = DepartmentStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
