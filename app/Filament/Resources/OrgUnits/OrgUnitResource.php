<?php

namespace App\Filament\Resources\OrgUnits;

use App\Filament\Resources\OrgUnits\Pages\CreateOrgUnit;
use App\Filament\Resources\OrgUnits\Pages\EditOrgUnit;
use App\Filament\Resources\OrgUnits\Pages\ListOrgUnits;
use App\Filament\Resources\OrgUnits\Schemas\OrgUnitForm;
use App\Filament\Resources\OrgUnits\Tables\OrgUnitsTable;
use App\Models\OrgUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrgUnitResource extends Resource
{
    protected static ?string $model = OrgUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Organization Units';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return OrgUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrgUnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrgUnits::route('/'),
            'create' => CreateOrgUnit::route('/create'),
            'edit' => EditOrgUnit::route('/{record}/edit'),
        ];
    }
}
