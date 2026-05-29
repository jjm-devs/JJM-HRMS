<?php

namespace App\Filament\Resources\PayMatrices\Pages;

use App\Filament\Resources\PayMatrices\PayMatrixResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayMatrices extends ListRecords
{
    protected static string $resource = PayMatrixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
