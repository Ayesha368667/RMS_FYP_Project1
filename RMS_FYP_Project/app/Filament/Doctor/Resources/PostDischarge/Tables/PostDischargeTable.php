<?php

namespace App\Filament\Doctor\Resources\PostDischarge\Tables;

use App\Filament\Doctor\Resources\PostDischarge\PostDischargeResource;
use App\Models\Admission;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostDischargeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // running follow-ups first, ended ones last
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['patient.user'])
                ->orderByRaw('(admissions.followup_end_date IS NOT NULL AND admissions.followup_end_date < CURDATE()) ASC')
                ->orderBy('admissions.followup_end_date'))
            ->columns([
                TextColumn::make('patient.patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('discharge_date')
                    ->label('Discharged')
                    ->date('d M Y'),

                TextColumn::make('followup_end_date')
                    ->label('Follow-up period')
                    ->state(function (Admission $record): string {
                        if (! $record->followup_start_date) {
                            return '-';
                        }

                        return $record->followup_start_date->format('d M')
                            .' to '
                            .($record->followup_end_date?->format('d M Y') ?? 'open');
                    }),

                TextColumn::make('followup_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Admission $record): string => PostDischargeResource::followupState($record)[0])
                    ->color(fn (Admission $record): string => PostDischargeResource::followupState($record)[1]),

                TextColumn::make('days_left')
                    ->label('Days left')
                    ->state(fn (Admission $record): ?int => PostDischargeResource::daysLeft($record))
                    ->placeholder('-'),

                TextColumn::make('compliance')
                    ->label('Logs submitted')
                    ->badge()
                    ->state(fn (Admission $record): string => PostDischargeResource::compliance($record)[0])
                    ->color(fn (Admission $record): string => PostDischargeResource::compliance($record)[1]),

                TextColumn::make('last_log_at')
                    ->label('Last log')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never'),

                TextColumn::make('open_alerts_count')
                    ->label('Alerts')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('followup_state')
                    ->label('Follow-up status')
                    ->options([
                        'active' => 'Active',
                        'ending_soon' => 'Ending soon (7 days)',
                        'ended' => 'Ended',
                        'not_started' => 'Not started',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query
                                ->whereDate('admissions.followup_start_date', '<=', today())
                                ->where(fn (Builder $w) => $w
                                    ->whereNull('admissions.followup_end_date')
                                    ->orWhereDate('admissions.followup_end_date', '>=', today())),
                            'ending_soon' => $query
                                ->whereDate('admissions.followup_start_date', '<=', today())
                                ->whereDate('admissions.followup_end_date', '>=', today())
                                ->whereDate('admissions.followup_end_date', '<=', today()->addDays(7)),
                            'ended' => $query->whereDate('admissions.followup_end_date', '<', today()),
                            'not_started' => $query->whereDate('admissions.followup_start_date', '>', today()),
                            default => $query,
                        };
                    }),
            ])
            ->recordUrl(fn (Admission $record): string => PostDischargeResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Open'),
            ])
            ->poll('60s')
            ->emptyStateHeading('No discharged patients')
            ->emptyStateDescription('Patients appear here after the Admin discharges them.');
    }
}