<?php

namespace App\Filament\Admin\Resources\Doctors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Schemas\Schema;

class DoctorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('staff_number')
                ->label('Staff number')
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit'),

            TextInput::make('name')
                ->label('Full name')
                ->required()
                ->maxLength(150),

            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(150)
                ->unique(
                    table: 'users',
                    column: 'email',
                    ignorable: function ($record) {
                        return $record?->user;
                    }
                ),

            TextInput::make('phone')
                ->maxLength(20),

            Select::make('preffered_language')
                ->label('Preferred language')
                ->options([
                    'en' => 'English',
                    'ur' => 'Urdu',
                ])
                ->default('en')
                ->required(),

            TextInput::make('qualification')
                ->required()
                ->maxLength(255),

            Select::make('professional_type')
                ->label('Type')
                ->options([
                    'doctor'     => 'Doctor',
                    'therapist'  => 'Therapist',
                ])
                ->required(),

            TextInput::make('specialization')
                ->required()
                ->maxLength(150),

            TextInput::make('license_number')
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            DatePicker::make('joining_date')
                ->required(),

            Select::make('availability_status')
                ->options([
                    'available'   => 'Available',
                    'on_leave'    => 'On leave',
                    'unavailable' => 'Unavailable',
                ])
                ->default('available')
                ->required(),
            ]);
    }
}
