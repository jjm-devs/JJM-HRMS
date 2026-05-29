<?php

namespace App\Filament\Resources\WorkflowSteps\Pages;

use App\Filament\Resources\WorkflowSteps\WorkflowStepResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowStep extends EditRecord
{
    protected static string $resource = WorkflowStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
