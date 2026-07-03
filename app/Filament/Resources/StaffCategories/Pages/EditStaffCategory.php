<?php

namespace App\Filament\Resources\StaffCategories\Pages;

use App\Filament\Resources\StaffCategories\StaffCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffCategory extends EditRecord
{
    protected static string $resource = StaffCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
