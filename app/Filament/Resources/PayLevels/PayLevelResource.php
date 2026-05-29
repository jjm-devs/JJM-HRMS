<?php

namespace App\Filament\Resources\PayLevels;

use App\Filament\Resources\PayLevels\Pages\CreatePayLevel;
use App\Filament\Resources\PayLevels\Pages\EditPayLevel;
use App\Filament\Resources\PayLevels\Pages\ListPayLevels;
use App\Filament\Resources\PayLevels\Schemas\PayLevelForm;
use App\Filament\Resources\PayLevels\Tables\PayLevelsTable;
use App\Models\PayLevel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayLevelResource extends Resource
{
    protected static ?string $model = PayLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Pay Levels';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return PayLevelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayLevelsTable::configure($table);
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
            'index' => ListPayLevels::route('/'),
            'create' => CreatePayLevel::route('/create'),
            'edit' => EditPayLevel::route('/{record}/edit'),
        ];
    }
}
