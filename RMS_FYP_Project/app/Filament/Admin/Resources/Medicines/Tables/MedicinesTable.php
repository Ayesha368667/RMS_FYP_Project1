<?php

namespace App\Filament\Admin\Resources\Medicines\Tables;

use App\Models\Medicine;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MedicinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Medicine')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('generic_name')
                    ->label('Generic name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('category')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('form')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => Medicine::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            // no delete button on purpose: medicines already given to patients
            // are linked by a foreign key, so use the "Active" switch instead
            ->recordActions([
                EditAction::make(),
            ]);
    }
}