<?php

namespace App\Filament\Admin\Resources\Patients\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_number')->label('Patient ID')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Name')->searchable(),
                TextColumn::make('father_name')->label('Father name')->searchable(),
                TextColumn::make('user.phone')->label('Phone'),
                TextColumn::make('city'),
                TextColumn::make('status')->badge(),
                IconColumn::make('user.is_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('toggle')
                    ->label(function ($record) {
                        return $record->user->is_active ? 'Deactivate' : 'Activate';
                    })
                    ->color(function ($record) {
                        return $record->user->is_active ? 'danger' : 'success';
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $user = $record->user;
                        $user->is_active = ! $user->is_active;
                        $user->save();

                        Notification::make()
                            ->title($user->is_active ? 'Patient activated' : 'Patient deactivated')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }
}