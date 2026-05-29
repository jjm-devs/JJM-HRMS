<?php

namespace App\Filament\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('holiday_date')
                    ->required(),
                Select::make('type')
                    ->options([
                        'national' => 'National',
                        'state' => 'State',
                        'district' => 'District',
                        'office' => 'Office',
                    ])
                    ->default('state')
                    ->required(),
                TextInput::make('state')
                    ->maxLength(255),
                TextInput::make('district')
                    ->maxLength(255),
                Select::make('org_unit_id')
                    ->relationship('orgUnit', 'name')
                    ->label('Organization Unit')
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
