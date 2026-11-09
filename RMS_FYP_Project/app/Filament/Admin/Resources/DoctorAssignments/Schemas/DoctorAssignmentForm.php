<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Schemas;

use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\Patient;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DbSchema;

class DoctorAssignmentForm
{
    /**
     * Active doctors with their current load (number of active patients).
     */
    public static function doctorOptions(): array
    {
        return Doctor::query()
            ->with('user')
            ->withCount(['doctorAssignments as active_patients_count' => fn ($query) => $query->where('status', 'active')])
            ->when(
                DbSchema::hasColumn('doctors', 'employment_status'),
                fn ($query) => $query->where('employment_status', 'active')
            )
            ->get()
            ->mapWithKeys(fn (Doctor $doctor): array => [
                $doctor->id => trim(
                    ($doctor->user?->name ?? 'Doctor #'.$doctor->id)
                    .($doctor->staff_number ? ' ('.$doctor->staff_number.')' : '')
                    .' - '.$doctor->active_patients_count.' active patients'
                ),
            ])
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment')
                    ->columns(2)
                    ->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->options(fn (): array => Patient::query()
                                ->with('user')
                                ->whereNotIn('status', ['discharged', 'inactive'])
                                ->get()
                                ->mapWithKeys(fn (Patient $patient): array => [
                                    $patient->id => trim($patient->patient_number.' - '.($patient->user?->name ?? 'Unnamed')),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabledOn('edit'),

                        Select::make('doctor_id')
                            ->label('Doctor / therapist (with current load)')
                            ->options(fn (): array => self::doctorOptions())
                            ->searchable()
                            ->required()
                            ->disabledOn('edit')
                            ->rules([
                                fn (Get $get, ?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $alreadyActive = DoctorAssignment::query()
                                        ->where('patient_id', $get('patient_id'))
                                        ->where('doctor_id', $value)
                                        ->where('status', 'active')
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if ($alreadyActive) {
                                        $fail('This doctor is already actively assigned to this patient.');
                                    }
                                },
                            ]),

                        Select::make('assignment_role')
                            ->label('Role')
                            ->options([
                                'primary' => 'Primary doctor',
                                'supporting' => 'Supporting doctor',
                                'therapist' => 'Therapist',
                                'counsellor' => 'Counsellor',
                            ])
                            ->default('primary')
                            ->required(),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('active')
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Start date')
                            ->default(now())
                            ->required(),

                        DatePicker::make('end_date')
                            ->label('End date')
                            ->afterOrEqual('start_date'),

                        Textarea::make('notes')
                            ->label('Instructions / notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}