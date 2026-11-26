<?php

namespace App\Filament\Admin\Resources\Patients\Tables;

use App\Models\Admission;
use App\Models\Doctor;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                // NAYA: patient ki condition
                TextColumn::make('condition_type')
                    ->label('Condition')
                    ->badge()
                    ->placeholder('Not set')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('status')->badge(),
                IconColumn::make('user.is_active')->label('Active')->boolean(),
            ])

            ->searchPlaceholder('Search by Patient ID or name')
            ->filters([

                // 1. Status filter (patients table ka apna column)
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'registered' => 'Registered',
                        'admitted'   => 'Admitted',
                        'discharged' => 'Discharged',
                        'inactive'   => 'Inactive',
                    ]),

                // 2. NAYA: Condition type filter (patients table ka apna column)
                SelectFilter::make('condition_type')
                    ->label('Condition type')
                    ->options([
                        'drug_addiction' => 'Drug addiction',
                        'mental_health'  => 'Mental health',
                        'both'           => 'Both',
                    ]),

                // 3. Admission type filter (admissions table se)
                // Pehle isay galti se "Condition type" likha tha
                SelectFilter::make('admission_type')
                    ->label('Admission type')
                    ->options(function () {
                        return Admission::query()
                            ->distinct()
                            ->orderBy('admission_type')
                            ->pluck('admission_type', 'admission_type')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('admissions', function ($q) use ($data) {
                                $q->where('admission_type', $data['value']);
                            });
                        }

                        return $query;
                    }),

                // 4. Doctor filter (sirf active assignment wale patients)
                SelectFilter::make('doctor')
                    ->label('Doctor')
                    ->options(function () {
                        return Doctor::with('user')->get()->pluck('user.name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('doctorAssignments', function ($q) use ($data) {
                                $q->where('doctor_id', $data['value'])
                                  ->where('status', 'active');
                            });
                        }

                        return $query;
                    }),
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