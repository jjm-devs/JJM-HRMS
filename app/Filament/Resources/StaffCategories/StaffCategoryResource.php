<?php

namespace App\Filament\Resources\StaffCategories;

use App\Filament\Resources\StaffCategories\Pages\CreateStaffCategory;
use App\Filament\Resources\StaffCategories\Pages\EditStaffCategory;
use App\Filament\Resources\StaffCategories\Pages\ListStaffCategories;
use App\Filament\Resources\StaffCategories\Schemas\StaffCategoryForm;
use App\Filament\Resources\StaffCategories\Tables\StaffCategoriesTable;
use App\Models\StaffCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StaffCategoryResource extends Resource
{
    protected static ?string $model = StaffCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Staff Categories';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return StaffCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffCategories::route('/'),
            'create' => CreateStaffCategory::route('/create'),
            'edit' => EditStaffCategory::route('/{record}/edit'),
        ];
    }
}
