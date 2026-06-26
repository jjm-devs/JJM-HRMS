<?php

namespace App\Filament\Resources\SanctionActivities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SanctionActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Activity Name')
                    ->helperText('e.g. Support Activity (Training-HRD)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('staff_category')
                    ->label('Staff Category')
                    ->helperText('e.g. SMMU Specialist/staffs')
                    ->required()
                    ->maxLength(255),
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
