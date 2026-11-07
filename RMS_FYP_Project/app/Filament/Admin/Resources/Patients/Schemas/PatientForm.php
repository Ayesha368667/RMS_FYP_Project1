<?php

namespace App\Filament\Admin\Resources\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->label('Full name')
                ->required()
                ->maxLength(150),

            TextInput::make('father_name')
                ->label('Father name')
                ->maxLength(150),

            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(150)
                ->unique(table: 'users', column: 'email'),

            TextInput::make('phone')
                ->maxLength(20),

            Select::make('preffered_language')
                ->label('Preferred language')
                ->options([
                    'en' => 'English',
                    'ur' => 'Urdu',
                ])
                ->default('ur')
                ->required(),

            DatePicker::make('date_of_birth')
                ->maxDate(now()),

            Select::make('gender')
                ->options([
                    'male'   => 'Male',
                    'female' => 'Female',
                    'other'  => 'Other',
                ]),

            TextInput::make('city')
                ->maxLength(100),

            Textarea::make('address')
                ->rows(2),

            DatePicker::make('registration_date')
                ->default(now())
                ->required(),

            // Photo private folder mein jati hai, URL se nahi khulti
            FileUpload::make('photo_path')
                ->label('Photo')
                ->image()
                ->disk('local')
                ->directory('patient-photos')
                ->visibility('private')
                ->maxSize(2048),
            ]);
    }
}
