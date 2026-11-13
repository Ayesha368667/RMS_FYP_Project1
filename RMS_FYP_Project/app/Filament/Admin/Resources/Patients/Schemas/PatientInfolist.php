<?php

namespace App\Filament\Admin\Resources\Patients\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Patient information')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('patient_number')->label('Patient ID'),
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('father_name')->label('Father name')->placeholder('-'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('user.phone')->label('Phone')->placeholder('-'),
                    TextEntry::make('date_of_birth')->label('Date of birth')->date('d M Y')->placeholder('-'),
                    TextEntry::make('gender')->placeholder('-'),
                    TextEntry::make('city')->placeholder('-'),
                    TextEntry::make('address')->placeholder('-'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('registration_date')->label('Registered on')->date('d M Y'),
                    TextEntry::make('user.preffered_language')
                        ->label('Language')
                        ->formatStateUsing(function ($state) {
                            return $state === 'ur' ? 'Urdu' : 'English';
                        }),
                    IconEntry::make('user.is_active')->label('Account active')->boolean(),
                ]),
        ]);
    }
}