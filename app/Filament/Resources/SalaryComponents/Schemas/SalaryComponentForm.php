<?php

namespace App\Filament\Resources\SalaryComponents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SalaryComponentForm
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
                    ->options([
                        'earning' => 'Earning',
                        'deduction' => 'Deduction',
                        'employer_contribution' => 'Employer Contribution',
                    ])
                    ->required(),
                Select::make('calculation_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                        'formula' => 'Formula',
                    ])
                    ->default('fixed')
                    ->required(),
                TextInput::make('default_amount')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Textarea::make('formula')
                    ->columnSpanFull(),
                Toggle::make('is_taxable'),
                Toggle::make('is_deduction'),
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
