<?php

namespace App\Filament\Admin\Resources\Alerts\Pages;

use App\Filament\Admin\Resources\Alerts\AlertResource;
use App\Models\Alert;
use App\Models\DoctorAssignment;
use App\Models\Patient;
use Illuminate\Support\Facades\Cache;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAlerts extends ListRecords
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createManualAlert')
                ->label('Create Manual Alert')
                ->icon('heroicon-o-plus')
                ->modalSubmitActionLabel('Create Alert')
                ->schema([
                    Select::make('patient_id')
                        ->label('Patient')
                        ->searchable()
                        ->required()
                        ->options(function () {
                            return Patient::with('user')->get()->pluck('user.name', 'id');
                        }),

                    Select::make('severity')
                        ->label('Severity')
                        ->required()
                        ->default('high')
                        ->options([
                            'medium' => 'Medium',
                            'high' => 'High',
                            'critical' => 'Critical',
                        ]),

                    Textarea::make('trigger_reason')
                        ->label('Reason')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) {

                    Alert::create([
                        'patient_id' => $data['patient_id'],
                        'alert_type' => 'manual',
                        'severity' => $data['severity'],
                        'trigger_reason' => $data['trigger_reason'],
                        'triggered_at' => now(),
                        'status' => 'open',
                        'raised_by' => auth()->id(),
                    ]);

                    // Notify active doctors
                    $assignments = DoctorAssignment::active()
                        ->where('patient_id', $data['patient_id'])
                        ->with('doctor.user')
                        ->get();

                    foreach ($assignments as $assignment) {
                        if ($assignment->doctor && $assignment->doctor->user) {
                            Notification::make()
                                ->danger()
                                ->title('New alert for your patient')
                                ->body($data['trigger_reason'])
                                ->sendToDatabase($assignment->doctor->user);
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Manual alert created')
                        ->send();
                }),
        ];
    }

    // Mark alerts as seen when the list opens
    public function mount(): void
    {
        parent::mount();

        Cache::forever(
            'alerts_last_seen_' . auth()->id(),
            now()->toDateTimeString()
        );
    }
}
