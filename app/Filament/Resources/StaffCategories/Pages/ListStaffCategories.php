<?php

namespace App\Filament\Resources\StaffCategories\Pages;

use App\Filament\Resources\StaffCategories\StaffCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffCategories extends ListRecords
{
    protected static string $resource = StaffCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
