<?php

namespace App\Filament\Resources\HrScopeAssignments\Schemas;

use App\Services\Hr\OrgUnitStreamService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class HrScopeAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('HR User')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('org_unit_id')
                    ->relationship('orgUnit', 'name')
                    ->label('Organization Unit')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('department_stream_id', null)),
                Toggle::make('is_ho')
                    ->label('Is Head Office HR User')
                    ->helperText('If enabled, this user will be identified as a Head Office HR user'),
                Toggle::make('include_child_units')
                    ->label('Include Child Units'),
                Select::make('department_stream_id')
                    ->options(fn (Get $get): array => app(OrgUnitStreamService::class)->activeOptionsFor($get('org_unit_id')))
                    ->label('Department Stream')
                    ->helperText('Leave empty to allow all streams within the selected organization scope.')
                    ->searchable()
                    ->preload(),
                Select::make('employment_type_id')
                    ->relationship('employmentType', 'name')
                    ->label('Employment Type')
                    ->helperText('Leave empty to allow all employment types within the selected organization scope.')
                    ->searchable()
                    ->preload(),
                Toggle::make('can_view')
                    ->default(true),
                Toggle::make('can_create'),
                Toggle::make('can_update'),
                Toggle::make('can_delete'),
                Toggle::make('can_approve'),
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
