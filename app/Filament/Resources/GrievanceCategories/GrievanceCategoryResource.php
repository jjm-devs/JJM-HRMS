<?php

namespace App\Filament\Resources\GrievanceCategories;

use App\Filament\Resources\GrievanceCategories\Pages\CreateGrievanceCategory;
use App\Filament\Resources\GrievanceCategories\Pages\EditGrievanceCategory;
use App\Filament\Resources\GrievanceCategories\Pages\ListGrievanceCategories;
use App\Filament\Resources\GrievanceCategories\Schemas\GrievanceCategoryForm;
use App\Filament\Resources\GrievanceCategories\Tables\GrievanceCategoriesTable;
use App\Models\GrievanceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GrievanceCategoryResource extends Resource
{
    protected static ?string $model = GrievanceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Grievance Categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return GrievanceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GrievanceCategoriesTable::configure($table);
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
            'index' => ListGrievanceCategories::route('/'),
            'create' => CreateGrievanceCategory::route('/create'),
            'edit' => EditGrievanceCategory::route('/{record}/edit'),
        ];
    }
}
