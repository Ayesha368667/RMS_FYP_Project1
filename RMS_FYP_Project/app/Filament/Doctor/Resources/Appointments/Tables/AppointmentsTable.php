<?php

namespace App\Filament\Doctor\Resources\Appointments\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Yeh line update karein (patient id ki jagah agar name ya koi aur column ho)
                TextColumn::make('patient_id')
                    ->label('Patient ID')
                    ->searchable()
                    ->sortable(),

                // Yeh line update karein
                TextColumn::make('doctor_id')
                    ->label('Doctor ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('appointment_date')
                    ->label('Appointment Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Temporary empty to bypass class error
            ])
            ->bulkActions([
                //
            ]);
    }
}