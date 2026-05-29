<?php

namespace App\Filament\Resources\PayLevels\Pages;

use App\Filament\Resources\PayLevels\PayLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayLevel extends EditRecord
{
    protected static string $resource = PayLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
