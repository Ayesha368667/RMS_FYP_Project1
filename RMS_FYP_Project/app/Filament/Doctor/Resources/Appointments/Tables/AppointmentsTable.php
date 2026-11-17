<?php

namespace App\Filament\Doctor\Resources\Appointments\Tables;

use App\Models\Appointment;
use App\Services\AppointmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // waiting requests first, then the newest
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['patient.user'])
                ->orderByRaw("status = 'requested' DESC")
                ->orderByDesc('requested_at'))
            ->columns([
                TextColumn::make('patient.patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('appointment_date')
                    ->label('Date and time')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not set yet')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Minutes')
                    ->toggleable(),

                TextColumn::make('appointment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'requested' => 'warning',
                        'approved' => 'success',
                        'completed' => 'info',
                        'rejected', 'missed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('reason')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'requested' => 'Requested',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'missed' => 'Missed',
                    ]),

                Filter::make('today')
                    ->label('Today only')
                    ->query(fn (Builder $query): Builder => $query->whereDate('appointment_date', today())),
            ])
            ->recordActions([
                // 1. approve a request
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Appointment $record): bool => $record->status === 'requested')
                    ->modalHeading('Approve appointment')
                    ->modalSubmitActionLabel('Approve')
                    ->fillForm(fn (Appointment $record): array => [
                        'duration_minutes' => $record->duration_minutes ?: 30,
                    ])
                    ->schema([
                        DateTimePicker::make('appointment_date')
                            ->label('Appointment date and time')
                            ->seconds(false)
                            ->minDate(now())
                            ->required(),

                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),
                    ])
                    ->action(fn (Appointment $record, array $data) => self::run(
                        fn () => app(AppointmentService::class)->approve($record, $data),
                        'Appointment approved'
                    )),

                // 2. reject a request (reason is compulsory)
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Appointment $record): bool => $record->status === 'requested')
                    ->modalHeading('Reject appointment request')
                    ->modalSubmitActionLabel('Reject')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(fn (Appointment $record, array $data) => self::run(
                        fn () => app(AppointmentService::class)->reject($record, $data['rejection_reason']),
                        'Appointment rejected'
                    )),

                // 3. move an upcoming approved appointment
                Action::make('reschedule')
                    ->label('Reschedule')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Appointment $record): bool => $record->status === 'approved'
                        && $record->appointment_date?->isFuture())
                    ->modalHeading('Reschedule appointment')
                    ->modalSubmitActionLabel('Save new time')
                    ->fillForm(fn (Appointment $record): array => [
                        'appointment_date' => $record->appointment_date,
                        'duration_minutes' => $record->duration_minutes,
                    ])
                    ->schema([
                        DateTimePicker::make('appointment_date')
                            ->label('New date and time')
                            ->seconds(false)
                            ->minDate(now())
                            ->required(),

                        TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(180)
                            ->required(),
                    ])
                    ->action(fn (Appointment $record, array $data) => self::run(
                        fn () => app(AppointmentService::class)->reschedule($record, $data),
                        'Appointment rescheduled'
                    )),

                // 4. cancel an upcoming approved appointment
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (Appointment $record): bool => $record->status === 'approved'
                        && $record->appointment_date?->isFuture())
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this appointment?')
                    ->action(fn (Appointment $record) => self::run(
                        fn () => app(AppointmentService::class)->cancel($record),
                        'Appointment cancelled'
                    )),
            ])
            ->emptyStateHeading('No appointments yet');
    }

    // runs a service call and shows a green or red message
    public static function run(callable $callback, string $successTitle): void
    {
        try {
            $callback();

            Notification::make()->title($successTitle)->success()->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}