<?php

namespace App\Filament\Resources\OrgUnits\Schemas;

use App\Models\OrgUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrgUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options(OrgUnit::TYPES)
                    ->required()
                    ->searchable(),
                Select::make('departmentStreams')
                    ->relationship('departmentStreams', 'name', fn ($query) => $query->where('status', 'active')->orderBy('name'))
                    ->label('Department Streams')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Select the streams that are available under this organization unit.'),
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->label('Parent Unit')
                    ->searchable()
                    ->preload(),
                TextInput::make('district_id')
                    ->numeric(),
                Textarea::make('address')
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
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
