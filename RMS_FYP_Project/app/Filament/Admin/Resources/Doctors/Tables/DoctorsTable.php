<?php

namespace App\Filament\Admin\Resources\Doctors\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DoctorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff_number')->label('Staff No')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Name')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('professional_type')->label('Type')->badge(),
                TextColumn::make('specialization')->searchable(),
                TextColumn::make('license_number')->searchable(),
                IconColumn::make('user.is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('professional_type')
                    ->label('Type')
                    ->options([
                        'doctor'     => 'Doctor',
                        'therapist'  => 'Therapist',
                    ]),
            ])
            ->recordActions([
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
                            ->title($user->is_active ? 'Doctor activated' : 'Doctor deactivated')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }
}