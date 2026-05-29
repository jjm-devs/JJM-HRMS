<?php

namespace App\Filament\Resources\IntegrationSettings\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IntegrationSettingForm
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
                TextInput::make('provider')
                    ->required()
                    ->maxLength(255),
                TextInput::make('base_url')
                    ->url()
                    ->maxLength(255),
                KeyValue::make('credentials')
                    ->columnSpanFull(),
                KeyValue::make('configuration')
                    ->columnSpanFull(),
                Toggle::make('enabled')
                    ->default(false),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('inactive')
                    ->required(),
            ]);
    }
}
