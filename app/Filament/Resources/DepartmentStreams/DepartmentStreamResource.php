<?php

namespace App\Filament\Resources\DepartmentStreams;

use App\Filament\Resources\DepartmentStreams\Pages\CreateDepartmentStream;
use App\Filament\Resources\DepartmentStreams\Pages\EditDepartmentStream;
use App\Filament\Resources\DepartmentStreams\Pages\ListDepartmentStreams;
use App\Filament\Resources\DepartmentStreams\Schemas\DepartmentStreamForm;
use App\Filament\Resources\DepartmentStreams\Tables\DepartmentStreamsTable;
use App\Models\DepartmentStream;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DepartmentStreamResource extends Resource
{
    protected static ?string $model = DepartmentStream::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Department Streams';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DepartmentStreamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentStreamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartmentStreams::route('/'),
            'create' => CreateDepartmentStream::route('/create'),
            'edit' => EditDepartmentStream::route('/{record}/edit'),
        ];
    }
}
