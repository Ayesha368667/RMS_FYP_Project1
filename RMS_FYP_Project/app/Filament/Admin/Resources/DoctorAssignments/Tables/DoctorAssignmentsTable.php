<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DoctorAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.user', 'doctor.user']))
            ->columns([
                TextColumn::make('patient.patient_number')
                    ->label('Patient no.')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor / therapist')
                    ->searchable(),

                TextColumn::make('assignment_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('assignment_role')
                    ->label('Role')
                    ->options([
                        'primary' => 'Primary doctor',
                        'supporting' => 'Supporting doctor',
                        'therapist' => 'Therapist',
                        'counsellor' => 'Counsellor',
                    ]),
            ])
            // no delete button: assignments are history and must be kept
            ->recordActions([
                Action::make('end')
                    ->label('End assignment')
                    ->icon('heroicon-o-stop-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->action(fn ($record) => $record->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ])),

                EditAction::make(),
            ]);
    }
}