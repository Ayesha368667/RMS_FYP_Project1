<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'admissions';

    protected static ?string $title = 'Admissions';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('admission_number')
            ->columns([
                TextColumn::make('admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('admission_date')
                    ->label('Admitted on')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('admission_type')
                    ->label('Condition type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admitted' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('discharge_date')
                    ->label('Discharged on')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('admission_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'admitted' => 'Admitted',
                        'discharged' => 'Discharged',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('No admissions yet');
    }
}