<?php

namespace App\Filament\Resources\PayMatrices;

use App\Filament\Resources\PayMatrices\Pages\CreatePayMatrix;
use App\Filament\Resources\PayMatrices\Pages\EditPayMatrix;
use App\Filament\Resources\PayMatrices\Pages\ListPayMatrices;
use App\Filament\Resources\PayMatrices\Schemas\PayMatrixForm;
use App\Filament\Resources\PayMatrices\Tables\PayMatricesTable;
use App\Models\PayMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayMatrixResource extends Resource
{
    protected static ?string $model = PayMatrix::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Pay Matrices';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return PayMatrixForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayMatricesTable::configure($table);
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
            'index' => ListPayMatrices::route('/'),
            'create' => CreatePayMatrix::route('/create'),
            'edit' => EditPayMatrix::route('/{record}/edit'),
        ];
    }
}
