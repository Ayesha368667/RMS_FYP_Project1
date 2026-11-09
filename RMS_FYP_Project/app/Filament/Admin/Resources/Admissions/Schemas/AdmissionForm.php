<?php

namespace App\Filament\Admin\Resources\Admissions\Schemas;

use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class AdmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(fn (): array => Patient::query()
                                ->with('user')
                                ->where('status', '!=', 'inactive')
                                ->whereDoesntHave('admissions', fn ($query) => $query->where('status', 'admitted'))
                                ->get()
                                ->mapWithKeys(fn (Patient $patient): array => [
                                    $patient->id => trim($patient->patient_number.' - '.($patient->user?->name ?? 'Unnamed')),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabledOn('edit')
                            ->helperText('Only patients without an active admission are listed.'),
                    ]),

                Section::make('Admission details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('admission_number')
                            ->label('Admission number')
                            ->placeholder('Generated automatically')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('admission_date')
                            ->label('Admission date and time')
                            ->default(now())
                            ->seconds(false)
                            ->required(),

                        Select::make('admission_type')
                            ->label('Condition / admission type')
                            ->options([
                                'new' => 'New admission',
                                'readmission' => 'Readmission',
                                'transfer' => 'Transfer',
                            ])
                            ->default('new')
                            ->required(),

                        TextInput::make('referral_source')
                            ->label('Referral source')
                            ->maxLength(150),

                        Textarea::make('admission_reason')
                            ->label('Reason for admission')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('admission_photo_path')
                            ->label('Admission photo (private, optional)')
                            ->image()
                            ->disk('local')
                            ->directory('admission-photos')
                            ->visibility('private')
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options([
                                'admitted' => 'Admitted',
                                'discharged' => 'Discharged',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                    ]),

                Section::make('Discharge and follow-up')
                    ->columns(2)
                    ->visible(fn (?Model $record): bool => $record?->status === 'discharged')
                    ->schema([
                        DateTimePicker::make('discharge_date')
                            ->label('Discharged on')
                            ->seconds(false)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('discharge_type')
                            ->label('Discharge type')
                            ->options([
                                'planned' => 'Planned discharge',
                                'referred' => 'Referred to another facility',
                                'left_early' => 'Left early',
                                'other' => 'Other',
                            ])
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('followup_start_date')
                            ->label('Follow-up start')
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('followup_end_date')
                            ->label('Follow-up end')
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('followup_frequency')
                            ->label('Follow-up frequency')
                            ->options([
                                'daily' => 'Every day',
                                'alternate_days' => 'Every other day',
                                'weekly' => 'Once a week',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}