<?php

namespace App\Filament\Resources\Designations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DesignationForm
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
                Select::make('cadre_id')
                    ->relationship('cadre', 'name')
                    ->label('Cadre')
                    ->searchable()
                    ->preload(),
                Select::make('department_stream_id')
                    ->relationship('departmentStream', 'name')
                    ->label('Department Stream')
                    ->searchable()
                    ->preload(),
                TextInput::make('level')
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
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
