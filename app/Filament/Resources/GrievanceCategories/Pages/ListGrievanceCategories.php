<?php

namespace App\Filament\Resources\GrievanceCategories\Pages;

use App\Filament\Resources\GrievanceCategories\GrievanceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGrievanceCategories extends ListRecords
{
    protected static string $resource = GrievanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
