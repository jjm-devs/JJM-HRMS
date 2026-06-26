<?php

namespace App\Filament\Resources\SanctionActivities\Pages;

use App\Filament\Resources\SanctionActivities\SanctionActivityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSanctionActivity extends EditRecord
{
    protected static string $resource = SanctionActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
