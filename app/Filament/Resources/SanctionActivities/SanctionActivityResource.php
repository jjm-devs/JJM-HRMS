<?php

namespace App\Filament\Resources\SanctionActivities;

use App\Filament\Resources\SanctionActivities\Pages\CreateSanctionActivity;
use App\Filament\Resources\SanctionActivities\Pages\EditSanctionActivity;
use App\Filament\Resources\SanctionActivities\Pages\ListSanctionActivities;
use App\Filament\Resources\SanctionActivities\Schemas\SanctionActivityForm;
use App\Filament\Resources\SanctionActivities\Tables\SanctionActivitiesTable;
use App\Models\SanctionActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SanctionActivityResource extends Resource
{
    protected static ?string $model = SanctionActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Sanction Activities';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return SanctionActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SanctionActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSanctionActivities::route('/'),
            'create' => CreateSanctionActivity::route('/create'),
            'edit' => EditSanctionActivity::route('/{record}/edit'),
        ];
    }
}
