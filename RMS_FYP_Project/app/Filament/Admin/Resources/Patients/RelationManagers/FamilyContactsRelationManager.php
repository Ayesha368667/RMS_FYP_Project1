<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'familyContacts';

    protected static ?string $title = 'Family contacts';

    /*
    Samajhne ki baat:
        Consent ki do hi values hain: granted (SMS bhej sakte hain) aur not_granted. Week 4 mein family SMS sirf granted wale contact ko jayega.
        Consent badalne par consent_recorded_at khud bhar jata hai (Step 1 ka booted() wala hissa).
        Delete ka button nahi, sirf Deactivate.
    */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('contact_name')
                ->label('Name')
                ->required()
                ->maxLength(150),

            Select::make('relationship')
                ->options([
                    'father'   => 'Father',
                    'mother'   => 'Mother',
                    'brother'  => 'Brother',
                    'sister'   => 'Sister',
                    'spouse'   => 'Spouse',
                    'son'      => 'Son',
                    'daughter' => 'Daughter',
                    'other'    => 'Other',
                ])
                ->required(),

            TextInput::make('phone')
                ->required()
                ->maxLength(20),

            TextInput::make('alternate_phone')
                ->maxLength(20),

            Select::make('consent_status')
                ->label('SMS consent')
                ->options([
                    'granted'     => 'Granted',
                    'not_granted' => 'Not granted',
                ])
                ->default('not_granted')
                ->required(),

            Select::make('preffered_language')
                ->label('SMS language')
                ->options([
                    'en' => 'English',
                    'ur' => 'Urdu',
                ])
                ->default('ur')
                ->required(),

            Toggle::make('is_primary')
                ->label('Primary contact'),

            Toggle::make('is_emergency_contact')
                ->label('Emergency contact'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contact_name')
            ->columns([
                TextColumn::make('contact_name')->label('Name'),
                TextColumn::make('relationship'),
                TextColumn::make('phone'),
                TextColumn::make('consent_status')
                    ->label('Consent')
                    ->badge()
                    ->color(function ($state) {
                        return $state === 'granted' ? 'success' : 'gray';
                    }),
                TextColumn::make('preffered_language')->label('Language'),
                IconColumn::make('is_primary')->label('Primary')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('toggle')
                    ->label(function ($record) {
                        return $record->is_active ? 'Deactivate' : 'Activate';
                    })
                    ->color(function ($record) {
                        return $record->is_active ? 'danger' : 'success';
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->is_active = ! $record->is_active;
                        $record->save();

                        Notification::make()
                            ->title($record->is_active ? 'Contact activated' : 'Contact deactivated')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}