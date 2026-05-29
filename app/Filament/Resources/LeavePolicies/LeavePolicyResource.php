<?php

namespace App\Filament\Resources\LeavePolicies;

use App\Filament\Resources\LeavePolicies\Pages\CreateLeavePolicy;
use App\Filament\Resources\LeavePolicies\Pages\EditLeavePolicy;
use App\Filament\Resources\LeavePolicies\Pages\ListLeavePolicies;
use App\Filament\Resources\LeavePolicies\Schemas\LeavePolicyForm;
use App\Filament\Resources\LeavePolicies\Tables\LeavePoliciesTable;
use App\Models\LeavePolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LeavePolicyResource extends Resource
{
    protected static ?string $model = LeavePolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Super Master';

    protected static ?string $navigationLabel = 'Leave Policies';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return LeavePolicyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeavePoliciesTable::configure($table);
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
            'index' => ListLeavePolicies::route('/'),
            'create' => CreateLeavePolicy::route('/create'),
            'edit' => EditLeavePolicy::route('/{record}/edit'),
        ];
    }
}
