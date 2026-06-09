<?php

namespace App\Filament\Resources\WorkflowSteps\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkflowStepForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('workflow_definition_id')
                    ->relationship('workflowDefinition', 'name')
                    ->label('Workflow')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sequence')
                    ->numeric()
                    ->default(1)
                    ->required(),
                Select::make('role')
                    ->options(Role::LABELS + [
                        'reporting_officer' => 'Reporting Officer',
                        'department_admin' => 'Department Admin',
                        'district_admin' => 'District Admin',
                        'approver' => 'Approver',
                    ])
                    ->required(),
                Select::make('action_type')
                    ->options([
                        'review' => 'Review',
                        'verify' => 'Verify',
                        'recommend' => 'Recommend',
                        'approve' => 'Approve',
                        'notify' => 'Notify',
                    ])
                    ->default('approve')
                    ->required(),
                TextInput::make('sla_hours')
                    ->numeric(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
