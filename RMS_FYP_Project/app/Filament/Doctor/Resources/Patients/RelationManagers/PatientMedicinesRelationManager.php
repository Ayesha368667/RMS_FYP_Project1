<?php

namespace App\Filament\Doctor\Resources\Patients\RelationManagers;

use App\Models\Medicine;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class PatientMedicinesRelationManager extends RelationManager
{
    protected static string $relationship = 'patientMedicines';

    protected static ?string $title = 'Medicines';

    // allow prescribe and edit even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // only ACTIVE medicines from the admin's predefined list
                Select::make('medicine_id')
                    ->label('Medicine')
                    ->options(fn (): array => Medicine::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('dosage')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g. 500 mg'),

                TextInput::make('frequency')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g. Twice daily, after meals'),

                DatePicker::make('start_date')
                    ->default(now())
                    ->required(),

                DatePicker::make('end_date')
                    ->afterOrEqual('start_date'),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'discontinued' => 'Discontinued',
                    ])
                    ->default('active')
                    ->required()
                    ->visible(fn (?Model $record): bool => $record !== null),

                Textarea::make('instructions')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn ($query) => $query->with(['medicine', 'doctor.user']))
            ->columns([
                TextColumn::make('medicine.name')
                    ->label('Medicine')
                    ->searchable(),

                TextColumn::make('dosage'),

                TextColumn::make('frequency'),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'discontinued' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('doctor.user.name')
                    ->label('Prescribed by')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'discontinued' => 'Discontinued',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Prescribe medicine')
                    ->modalHeading('Prescribe medicine')
                    ->mutateDataUsing(function (array $data): array {
                        $patient = $this->getOwnerRecord();

                        $data['doctor_id'] = auth()->user()->doctor->id;
                        $data['status'] = 'active';
                        // link to the patient's current admission, if there is one
                        $data['admission_id'] = $patient->admissions()
                            ->where('status', 'admitted')
                            ->value('id');

                        return $data;
                    })
                    ->after(fn (Model $record) => $this->notifyPatient(
                        $record,
                        'A new medicine was added for you',
                        'Your doctor prescribed '.$record->medicine?->name.' ('.$record->dosage.', '.$record->frequency.').'
                    )),
            ])
            // no delete: a medicine is only completed or discontinued, never removed
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit medicine')
                    ->after(fn (Model $record) => $this->notifyPatient(
                        $record,
                        'Your medicine details were updated',
                        'Your doctor updated '.$record->medicine?->name.'. Please check your medicines page.'
                    )),
            ])
            ->emptyStateHeading('No medicines prescribed yet');
    }

    private function notifyPatient(Model $record, string $title, string $body): void
    {
        try {
            $user = $this->getOwnerRecord()->user;

            if (! $user) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-beaker')
                ->sendToDatabase($user);
        } catch (Throwable $e) {
            // a notification problem must never stop saving the medicine
            report($e);
        }
    }
}