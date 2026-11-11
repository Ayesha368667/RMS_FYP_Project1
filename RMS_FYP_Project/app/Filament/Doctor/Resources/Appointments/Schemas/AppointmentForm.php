<?php

namespace App\Filament\Doctor\Resources\Appointments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('patient_id')
                    ->relationship('patient', 'id') // Agar patient ka naam show karna ho toh relationship column adjust kar sakte hain
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('doctor_id')
                    ->relationship('doctor', 'id')
                    ->required()
                    ->searchable()
                    ->preload(),

                DateTimePicker::make('appointment_date')
                    ->required(),

                Select::make('appointment_type')
                    ->options([
                        'consultation' => 'Consultation',
                        'follow_up' => 'Follow Up',
                        'emergency' => 'Emergency',
                    ])
                    ->required(),

                TextInput::make('duration_minutes')
                    ->numeric()
                    ->default(30),

                Textarea::make('reason')
                    ->columnSpanFull(),
            ]);
    }
}