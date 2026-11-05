<?php

namespace App\Filament\Admin\Resources\Medicines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MedicineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicine details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Medicine name')
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),

                        TextInput::make('generic_name')
                            ->label('Generic name')
                            ->maxLength(150),

                        TextInput::make('category')
                            ->label('Category')
                            ->maxLength(100),

                        Select::make('form')
                            ->label('Form')
                            ->options([
                                'tablet' => 'Tablet',
                                'capsule' => 'Capsule',
                                'syrup' => 'Syrup',
                                'injection' => 'Injection',
                                'drops' => 'Drops',
                                'cream' => 'Cream',
                                'inhaler' => 'Inhaler',
                                'other' => 'Other',
                            ]),

                        Textarea::make('description')
                            ->label('Description / notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Available for prescribing')
                            ->default(true),
                    ]),
            ]);
    }
}

