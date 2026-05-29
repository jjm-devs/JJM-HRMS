<?php

namespace App\Filament\Resources\PayMatrices\Pages;

use App\Filament\Resources\PayMatrices\PayMatrixResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayMatrix extends EditRecord
{
    protected static string $resource = PayMatrixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
