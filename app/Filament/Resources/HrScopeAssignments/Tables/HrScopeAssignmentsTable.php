<?php

namespace App\Filament\Resources\HrScopeAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HrScopeAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('HR User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('orgUnit.name')
                    ->label('Organization Unit')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_ho')
                    ->boolean()
                    ->label('Head Office'),
                IconColumn::make('include_child_units')
                    ->boolean()
                    ->label('Child Units'),
                TextColumn::make('departmentStream.name')
                    ->label('Stream')
                    ->placeholder('All'),
                TextColumn::make('employmentType.name')
                    ->label('Employment Type')
                    ->placeholder('All'),
                IconColumn::make('can_view')
                    ->boolean(),
                IconColumn::make('can_create')
                    ->boolean(),
                IconColumn::make('can_update')
                    ->boolean(),
                IconColumn::make('can_approve')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
