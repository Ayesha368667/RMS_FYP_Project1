<?php

namespace App\Filament\Admin\Resources\Patients\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PatientsTable
{
    /**
     * The active assignment of the logged-in doctor for this patient.
     */
    private static function myAssignment($patient)
    {
        $doctorId = (int) auth()->user()?->doctor?->id;

        return $patient->doctorAssignments->first(
            fn ($assignment): bool => (int) $assignment->doctor_id === $doctorId
                && $assignment->status === 'active'
        );
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_number')
                    ->label('Patient ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),

                TextColumn::make('age')
                    ->label('Age')
                    ->state(fn ($record): string => $record->date_of_birth
                        ? $record->date_of_birth->age.' yrs'
                        : '-'),

                TextColumn::make('gender')
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.phone')
                    ->label('Phone')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admitted' => 'success',
                        'registered' => 'info',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('my_role')
                    ->label('My role')
                    ->state(fn ($record): string => ucfirst((string) (self::myAssignment($record)?->assignment_role ?? '-'))),

                TextColumn::make('assigned_since')
                    ->label('Assigned since')
                    ->state(fn ($record): string => self::myAssignment($record)?->start_date?->format('d M Y') ?? '-'),
            ])
            ->defaultSort('patient_number')
            ->searchPlaceholder('Search by Patient ID or name')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Registered',
                        'admitted' => 'Admitted',
                        'discharged' => 'Discharged',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Open profile'),
            ])
            ->emptyStateHeading('No patients assigned to you yet')
            ->emptyStateDescription('Patients appear here once the admin assigns them to you.');
    }
}