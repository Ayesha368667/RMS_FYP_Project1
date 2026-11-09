<?php

namespace App\Filament\Admin\Resources\Admissions\Tables;

use App\Services\AdmissionService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use InvalidArgumentException;

class AdmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['patient.user']))
            ->columns([
                TextColumn::make('admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.patient_number')
                    ->label('Patient no.')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('admission_date')
                    ->label('Admitted on')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('admission_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admitted' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('discharge_date')
                    ->label('Discharged on')
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('followup_frequency')
                    ->label('Follow-up')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state, $record): string => $record->status === 'discharged'
                        ? match ($state) {
                            'daily' => 'Every day',
                            'alternate_days' => 'Every other day',
                            'weekly' => 'Once a week',
                            default => (string) $state,
                        }
                        : '-')
                    ->toggleable(),

                TextColumn::make('followup_end_date')
                    ->label('Follow-up ends')
                    ->date()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('admission_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'admitted' => 'Admitted',
                        'discharged' => 'Discharged',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('admission_type')
                    ->label('Type')
                    ->options([
                        'new' => 'New admission',
                        'readmission' => 'Readmission',
                        'transfer' => 'Transfer',
                    ]),
            ])
            // no delete button: admissions are medical history and must be kept
            ->recordActions([
                Action::make('discharge')
                    ->label('Discharge')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn ($record): bool => $record->status === 'admitted')
                    ->modalHeading('Discharge patient')
                    ->modalSubmitActionLabel('Discharge')
                    ->schema([
                        Section::make('Discharge details')
                            ->columns(2)
                            ->schema([
                                DateTimePicker::make('discharge_date')
                                    ->label('Discharge date and time')
                                    ->default(now())
                                    ->seconds(false)
                                    ->required(),

                                Select::make('discharge_type')
                                    ->label('Discharge type')
                                    ->options([
                                        'planned' => 'Planned discharge',
                                        'referred' => 'Referred to another facility',
                                        'left_early' => 'Left early',
                                        'other' => 'Other',
                                    ])
                                    ->default('planned')
                                    ->required(),
                            ]),

                        Section::make('Follow-up plan')
                            ->columns(3)
                            ->schema([
                                DatePicker::make('followup_start_date')
                                    ->label('Follow-up start')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('followup_end_date')
                                    ->label('Follow-up end')
                                    ->default(now()->addDays(90))
                                    ->afterOrEqual('followup_start_date')
                                    ->required(),

                                Select::make('followup_frequency')
                                    ->label('Frequency')
                                    ->options([
                                        'daily' => 'Every day',
                                        'alternate_days' => 'Every other day',
                                        'weekly' => 'Once a week',
                                    ])
                                    ->default('daily')
                                    ->required(),
                            ]),
                    ])
                    ->action(function ($record, array $data): void {
                        try {
                            app(AdmissionService::class)->discharge($record, $data);

                            Notification::make()
                                ->title('Patient discharged')
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ]);
    }
}