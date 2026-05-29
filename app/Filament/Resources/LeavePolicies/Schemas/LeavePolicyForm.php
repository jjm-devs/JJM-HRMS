<?php

namespace App\Filament\Resources\LeavePolicies\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeavePolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('leave_type_id')
                    ->relationship('leaveType', 'name')
                    ->label('Leave Type')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('employment_type_id')
                    ->relationship('employmentType', 'name')
                    ->label('Employment Type')
                    ->searchable()
                    ->preload(),
                Select::make('gender')
                    ->options([
                        'all' => 'All',
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                    ])
                    ->default('all'),
                Select::make('service_type')
                    ->options([
                        'all' => 'All',
                        'regular' => 'Regular',
                        'contractual' => 'Contractual',
                    ])
                    ->default('all'),
                TextInput::make('annual_quota')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('max_days_per_request')
                    ->numeric(),
                TextInput::make('carry_forward_limit')
                    ->numeric(),
                TextInput::make('encashable_limit')
                    ->numeric(),
                KeyValue::make('rules')
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
