<?php

namespace App\Filament\Resources\HrScopeAssignments\Pages;

use App\Filament\Resources\HrScopeAssignments\HrScopeAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHrScopeAssignment extends EditRecord
{
    protected static string $resource = HrScopeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
