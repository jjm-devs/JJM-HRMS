<?php

namespace App\Filament\Resources\PayLevels\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pay_matrix_id')
                    ->relationship('payMatrix', 'name')
                    ->label('Pay Matrix')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('level_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('min_basic')
                    ->numeric(),
                TextInput::make('max_basic')
                    ->numeric(),
                TextInput::make('increment_amount')
                    ->numeric(),
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
