<?php

namespace App\Filament\Resources\Cadres;

use App\Filament\Resources\Cadres\Pages\CreateCadre;
use App\Filament\Resources\Cadres\Pages\EditCadre;
use App\Filament\Resources\Cadres\Pages\ListCadres;
use App\Filament\Resources\Cadres\Schemas\CadreForm;
use App\Filament\Resources\Cadres\Tables\CadresTable;
use App\Models\Cadre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CadreResource extends Resource
{
    protected static ?string $model = Cadre::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Cadres';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CadreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CadresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCadres::route('/'),
            'create' => CreateCadre::route('/create'),
            'edit' => EditCadre::route('/{record}/edit'),
        ];
    }
}
