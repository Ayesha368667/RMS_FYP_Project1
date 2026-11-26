<?php

namespace App\Filament\Admin\Resources\FamilyContactLogs\Pages;

use App\Filament\Admin\Resources\FamilyContactLogs\FamilyContactLogResource;
use App\Models\Alert;
use App\Models\FamilyContact;
use App\Models\FamilyContactLog;
use App\Models\Patient;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ListFamilyContactLogs extends ListRecords
{
    protected static string $resource = FamilyContactLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addManualContact')
                ->label('Add Manual Contact')
                ->icon('heroicon-o-phone')
                ->modalSubmitActionLabel('Save Contact Log')
                ->schema([

                    // 1. Patient chuno
                    Select::make('patient_id')
                        ->label('Patient')
                        ->searchable()
                        ->required()
                        ->live()
                        ->options(function () {
                            return Patient::with('user')->get()->pluck('user.name', 'id');
                        })
                        ->afterStateUpdated(function (Set $set) {
                            // Patient badla to purani selection saaf
                            $set('family_contact_id', null);
                            $set('alert_id', null);
                        }),

                    // 2. Us patient ka active family member
                    Select::make('family_contact_id')
                        ->label('Family member')
                        ->required()
                        ->helperText('Sirf active contacts dikhte hain. Na milay to Patient ke Family contacts tab mein add karein.')
                        ->options(function (Get $get) {
                            $options = [];

                            if (! $get('patient_id')) {
                                return $options;
                            }

                            $contacts = FamilyContact::where('patient_id', $get('patient_id'))
                                ->where('is_active', true)
                                ->get();

                            foreach ($contacts as $contact) {
                                $options[$contact->id] = $contact->contact_name . ' (' . ucfirst($contact->relationship) . ') - ' . $contact->phone;
                            }

                            return $options;
                        }),

                    // 3. Optional: kisi alert se jodna
                    Select::make('alert_id')
                        ->label('Related alert (optional)')
                        ->options(function (Get $get) {
                            $options = [];

                            if (! $get('patient_id')) {
                                return $options;
                            }

                            $alerts = Alert::where('patient_id', $get('patient_id'))
                                ->where('status', '!=', 'resolved')
                                ->get();

                            foreach ($alerts as $alert) {
                                $options[$alert->id] = '#' . $alert->id . ' - ' . $alert->trigger_reason;
                            }

                            return $options;
                        }),

                    // 4. Call ka result
                    Select::make('outcome')
                        ->label('Call outcome')
                        ->required()
                        ->options([
                            'answered' => 'Answered',
                            'no_answer' => 'No answer',
                            'busy' => 'Busy',
                            'wrong_number' => 'Wrong number',
                        ]),

                    DateTimePicker::make('contacted_at')
                        ->label('Call time')
                        ->required()
                        ->seconds(false)
                        ->maxDate(now())
                        ->default(function () {
                            return now();
                        }),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {

                    FamilyContactLog::create([
                        'patient_id' => $data['patient_id'],
                        'alert_id' => $data['alert_id'] ?? null,
                        'family_contact_id' => $data['family_contact_id'],
                        'contacted_by' => auth()->id(),
                        'contact_type' => 'manual',
                        'channel' => 'call',
                        'contacted_at' => $data['contacted_at'],
                        'outcome' => $data['outcome'],
                        'remarks' => $data['remarks'] ?? null,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Contact log saved')
                        ->send();
                }),
        ];
    }
}