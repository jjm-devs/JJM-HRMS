<?php

namespace App\Filament\Resources\GrievanceCategories\Pages;

use App\Filament\Resources\GrievanceCategories\GrievanceCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGrievanceCategory extends EditRecord
{
    protected static string $resource = GrievanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
