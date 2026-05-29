<?php

namespace App\Filament\Resources\HrScopeAssignments;

use App\Filament\Resources\HrScopeAssignments\Pages\CreateHrScopeAssignment;
use App\Filament\Resources\HrScopeAssignments\Pages\EditHrScopeAssignment;
use App\Filament\Resources\HrScopeAssignments\Pages\ListHrScopeAssignments;
use App\Filament\Resources\HrScopeAssignments\Schemas\HrScopeAssignmentForm;
use App\Filament\Resources\HrScopeAssignments\Tables\HrScopeAssignmentsTable;
use App\Models\HrScopeAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HrScopeAssignmentResource extends Resource
{
    protected static ?string $model = HrScopeAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'HR Scope Assignments';

    protected static ?int $navigationSort = 18;

    public static function form(Schema $schema): Schema
    {
        return HrScopeAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HrScopeAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHrScopeAssignments::route('/'),
            'create' => CreateHrScopeAssignment::route('/create'),
            'edit' => EditHrScopeAssignment::route('/{record}/edit'),
        ];
    }
}
