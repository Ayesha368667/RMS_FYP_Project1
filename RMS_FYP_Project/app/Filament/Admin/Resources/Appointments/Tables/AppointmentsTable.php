<?php

namespace App\Filament\Admin\Resources\Appointments\Tables;

use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor')
                    ->searchable(),

                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('reason')
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('-'),

                TextColumn::make('requested_at')
                    ->label('Requested At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('appointment_date')
                    ->label('Appointment Time')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not set yet'),

                TextColumn::make('status')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'requested' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'completed' => 'info',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                // Pehli dafa sirf pending requests dikhengi
                SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'missed' => 'Missed',
                    ])
                    ->default('requested'),
            ])
            ->recordActions([

                // APPROVE: admin date/time choose karta hai
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (Appointment $record) {
                        return $record->status === 'requested';
                    })
                    ->schema([
                        DateTimePicker::make('appointment_date')
                            ->label('Appointment date & time')
                            ->required()
                            ->seconds(false)
                            ->minDate(now()),
                    ])
                    ->action(function (Appointment $record, array $data, Action $action) {

                        // Doctor us waqt pehle se booked to nahi?
                        $alreadyBooked = Appointment::where('doctor_id', $record->doctor_id)
                            ->where('appointment_date', $data['appointment_date'])
                            ->exists();

                        if ($alreadyBooked) {
                            Notification::make()
                                ->danger()
                                ->title('Doctor is already booked at this time')
                                ->send();

                            $action->halt();
                        }

                        $record->update([
                            'status' => 'approved',
                            'appointment_date' => $data['appointment_date'],
                            'approved_by' => auth()->id(),
                        ]);

                        // Patient ko notification
                        $user = $record->patient->user;

                        if ($user) {
                            Notification::make()
                                ->success()
                                ->title('Appointment approved')
                                ->body('Your appointment is on ' . $record->appointment_date->format('d M Y, h:i A'))
                                ->sendToDatabase($user);
                        }

                        Notification::make()
                            ->success()
                            ->title('Appointment approved')
                            ->send();
                    }),

                // REJECT: reason likhna zaroori hai
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Appointment $record) {
                        return $record->status === 'requested';
                    })
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Reason for rejection')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Appointment $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        $user = $record->patient->user;

                        if ($user) {
                            Notification::make()
                                ->danger()
                                ->title('Appointment rejected')
                                ->body('Reason: ' . $data['rejection_reason'])
                                ->sendToDatabase($user);
                        }

                        Notification::make()
                            ->success()
                            ->title('Appointment rejected')
                            ->send();
                    }),
            ]);
    }
}