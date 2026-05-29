<?php

namespace App\Filament\Resources\HrScopeAssignments\Pages;

use App\Filament\Resources\HrScopeAssignments\HrScopeAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHrScopeAssignments extends ListRecords
{
    protected static string $resource = HrScopeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
