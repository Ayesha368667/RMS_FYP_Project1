<?php

namespace App\Filament\Admin\Resources\Admissions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.user']))
            ->columns([
                TextColumn::make('admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.patient_number')
                    ->label('Patient no.')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('admission_date')
                    ->label('Admitted on')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('admission_type')
                    ->label('Type')
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
            ])
            ->defaultSort('admission_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'admitted' => 'Admitted',
                        'discharged' => 'Discharged',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('admission_type')
                    ->label('Type')
                    ->options([
                        'new' => 'New admission',
                        'readmission' => 'Readmission',
                        'transfer' => 'Transfer',
                    ]),
            ])
            // no delete button: admissions are medical history and must be kept
            ->recordActions([
                EditAction::make(),
            ]);
    }
}