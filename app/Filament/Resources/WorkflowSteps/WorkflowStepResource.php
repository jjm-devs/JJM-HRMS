<?php

namespace App\Filament\Resources\WorkflowSteps;

use App\Filament\Resources\WorkflowSteps\Pages\CreateWorkflowStep;
use App\Filament\Resources\WorkflowSteps\Pages\EditWorkflowStep;
use App\Filament\Resources\WorkflowSteps\Pages\ListWorkflowSteps;
use App\Filament\Resources\WorkflowSteps\Schemas\WorkflowStepForm;
use App\Filament\Resources\WorkflowSteps\Tables\WorkflowStepsTable;
use App\Models\WorkflowStep;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkflowStepResource extends Resource
{
    protected static ?string $model = WorkflowStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Workflow Steps';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return WorkflowStepForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowStepsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowSteps::route('/'),
            'create' => CreateWorkflowStep::route('/create'),
            'edit' => EditWorkflowStep::route('/{record}/edit'),
        ];
    }
}
