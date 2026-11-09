<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Tables;

use App\Filament\Admin\Resources\DoctorAssignments\DoctorAssignmentResource;
use App\Filament\Admin\Resources\DoctorAssignments\Schemas\DoctorAssignmentForm;
use App\Models\DoctorAssignment;
use App\Models\Patient;
use App\Services\DoctorAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class DoctorAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.user', 'doctor.user']))
            ->columns([
                TextColumn::make('patient.patient_number')
                    ->label('Patient no.')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('doctor.user.name')
                    ->label('Doctor / therapist')
                    ->searchable(),

                TextColumn::make('doctor_load')
                    ->label('Doctor load')
                    ->state(fn ($record): string => DoctorAssignment::query()
                        ->where('doctor_id', $record->doctor_id)
                        ->where('status', 'active')
                        ->count().' active'),

                TextColumn::make('assignment_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->searchable()
                    ->options(fn (): array => Patient::query()
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (Patient $patient): array => [
                            $patient->id => trim($patient->patient_number.' - '.($patient->user?->name ?? 'Unnamed')),
                        ])
                        ->all()),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('assignment_role')
                    ->label('Role')
                    ->options([
                        'primary' => 'Primary doctor',
                        'supporting' => 'Supporting doctor',
                        'therapist' => 'Therapist',
                        'counsellor' => 'Counsellor',
                    ]),
            ])
            // no delete button: assignments are history and must be kept
            ->recordActions([
                Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->schema([
                        Select::make('doctor_id')
                            ->label('New doctor (with current load)')
                            ->options(fn (): array => DoctorAssignmentForm::doctorOptions())
                            ->searchable()
                            ->required(),

                        Textarea::make('reason')
                            ->label('Reason for reassignment')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data): void {
                        try {
                            app(DoctorAssignmentService::class)->reassign(
                                $record,
                                (int) $data['doctor_id'],
                                $data['reason'] ?? null,
                            );

                            Notification::make()
                                ->title('Patient reassigned and notifications sent')
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('history')
                    ->label('History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn ($record): string => DoctorAssignmentResource::getUrl('index', [
                        'tableFilters' => ['patient_id' => ['value' => $record->patient_id]],
                    ])),

                Action::make('end')
                    ->label('End assignment')
                    ->icon('heroicon-o-stop-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->action(fn ($record) => $record->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ])),

                EditAction::make(),
            ]);
    }
}