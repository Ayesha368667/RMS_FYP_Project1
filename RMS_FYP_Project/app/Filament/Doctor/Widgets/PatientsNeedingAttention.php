<?php

namespace App\Filament\Doctor\Widgets;

use App\Filament\Doctor\Resources\Patients\PatientResource;
use App\Models\Patient;
use App\Support\DoctorDashboard;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PatientsNeedingAttention extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Patients who need attention')
            ->description('Critical logs not reviewed, or no response for '.DoctorDashboard::INACTIVE_DAYS.' days or more.')
            ->query(fn () => DoctorDashboard::attentionQuery())
            ->columns([
                TextColumn::make('patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Patient')
                    ->searchable(),

                // red badge = critical log, orange badge = not responding
                TextColumn::make('attention')
                    ->label('Why')
                    ->badge()
                    ->state(function (Patient $record): array {
                        $reasons = [];

                        if ((int) $record->critical_logs_count > 0) {
                            $reasons[] = 'Critical log';
                        }

                        $last = $record->last_log_at ? Carbon::parse($record->last_log_at) : null;

                        if (! $last || $last->lt(today()->subDays(DoctorDashboard::INACTIVE_DAYS))) {
                            $reasons[] = 'Not responding';
                        }

                        return $reasons;
                    })
                    ->color(fn (string $state): string => $state === 'Critical log' ? 'danger' : 'warning'),

                TextColumn::make('critical_logs_count')
                    ->label('Critical logs')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('last_log_at')
                    ->label('Last log')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('critical_logs_count', 'desc')
            ->recordUrl(fn (Patient $record): string => PatientResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10])
            ->poll('30s')
            ->emptyStateHeading('No patient needs attention right now')
            ->emptyStateDescription('Critical or silent patients will appear here.');
    }
}
