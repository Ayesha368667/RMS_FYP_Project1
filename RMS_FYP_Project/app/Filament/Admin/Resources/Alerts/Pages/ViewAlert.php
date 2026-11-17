<?php

namespace App\Filament\Admin\Resources\Alerts\Pages;

use App\Filament\Admin\Resources\Alerts\AlertResource;
use App\Models\AlertAction;
use App\Models\DoctorAssignment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAlert extends ViewRecord
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resolve')
                ->label('Resolve Alert')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->modalSubmitActionLabel('Resolve')
                // Resolved alert par button nahi dikhega
                ->visible(function () {
                    return $this->record->status !== 'resolved';
                })
                ->schema([
                    Textarea::make('resolution_notes')
                        ->label('Resolution notes')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {
                    $alert = $this->record;

                    $alert->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                        'resolved_by' => auth()->id(),
                        'resolution_notes' => $data['resolution_notes'],
                    ]);

                    // Action log mein bhi entry, taake history poori rahe
                    AlertAction::create([
                        'alert_id' => $alert->id,
                        'user_id' => auth()->id(),
                        'action_type' => 'resolved',
                        'notes' => $data['resolution_notes'],
                    ]);

                    // Patient ke active doctor(s) ko notification
                    $assignments = DoctorAssignment::active()
                        ->where('patient_id', $alert->patient_id)
                        ->with('doctor.user')
                        ->get();

                    foreach ($assignments as $assignment) {
                        if ($assignment->doctor && $assignment->doctor->user) {
                            Notification::make()
                                ->success()
                                ->title('Alert resolved')
                                ->body($data['resolution_notes'])
                                ->sendToDatabase($assignment->doctor->user);
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Alert resolved')
                        ->send();

                    // Page dobara kholo taake action log bhi refresh ho
                    $this->redirect(AlertResource::getUrl('view', ['record' => $alert]));
                }),
        ];
    }
}