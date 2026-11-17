<?php

namespace App\Filament\Doctor\Resources\Appointments\Pages;

use App\Filament\Doctor\Resources\Appointments\AppointmentResource;
use App\Filament\Doctor\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Patient;
use App\Services\AppointmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // the doctor schedules an appointment for one of his own patients
            Action::make('schedule')
                ->label('Schedule appointment')
                ->icon('heroicon-o-plus')
                ->modalHeading('Schedule appointment')
                ->modalSubmitActionLabel('Schedule')
                ->fillForm(['duration_minutes' => 30])
                ->schema([
                    Select::make('patient_id')
                        ->label('Patient')
                        ->options(function (): array {
                            $doctorId = auth()->user()?->doctor?->id;

                            return Patient::query()
                                ->with('user')
                                ->whereHas('activeAssignments', fn (Builder $q) => $q->where('doctor_id', $doctorId))
                                ->get()
                                ->mapWithKeys(fn (Patient $p) => [
                                    $p->id => $p->patient_number.' - '.($p->user?->name ?? ''),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->required(),

                    Select::make('appointment_type')
                        ->label('Type')
                        ->options([
                            'consultation' => 'Consultation',
                            'counselling' => 'Counselling',
                            'follow_up' => 'Follow up',
                        ])
                        ->required(),

                    DateTimePicker::make('appointment_date')
                        ->label('Date and time')
                        ->seconds(false)
                        ->minDate(now())
                        ->required(),

                    TextInput::make('duration_minutes')
                        ->label('Duration (minutes)')
                        ->numeric()
                        ->minValue(15)
                        ->maxValue(180)
                        ->required(),

                    Textarea::make('reason')
                        ->rows(2),
                ])
                ->action(fn (array $data) => AppointmentsTable::run(
                    fn () => app(AppointmentService::class)->schedule(
                        Patient::query()->findOrFail($data['patient_id']),
                        $data
                    ),
                    'Appointment scheduled'
                )),
        ];
    }
}