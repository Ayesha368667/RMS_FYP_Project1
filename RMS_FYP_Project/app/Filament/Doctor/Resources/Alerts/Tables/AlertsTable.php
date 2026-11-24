<?php

namespace App\Filament\Doctor\Resources\Alerts\Tables;

use App\Filament\Doctor\Resources\Alerts\AlertResource;
use App\Models\Alert;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // open alerts first, then under action, then resolved; newest first
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['patient.user'])
                ->orderByRaw("FIELD(status, 'open', 'under_action', 'resolved')")
                ->orderByDesc('triggered_at'))
            ->columns([
                TextColumn::make('triggered_at')
                    ->label('Triggered at')
                    ->dateTime('d M Y, h:i A'),

                TextColumn::make('patient.patient_number')
                    ->label('Patient ID')
                    ->searchable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('alert_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('severity')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'critical', 'high' => 'danger',
                        'medium' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'open' => 'danger',
                        'under_action' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('trigger_reason')
                    ->label('Reason')
                    ->limit(50)
                    ->tooltip(fn (Alert $record): string => (string) $record->trigger_reason),

                TextColumn::make('missed_count')
                    ->label('Missed days')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                // on by default: show only alerts that still need work
                Filter::make('unresolved')
                    ->label('Unresolved only')
                    ->toggle()
                    ->default()
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', ['open', 'under_action'])),

                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'under_action' => 'Under action',
                        'resolved' => 'Resolved',
                    ]),

                SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
            ])
            ->recordUrl(fn (Alert $record): string => AlertResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->label('Open'),
            ])
            ->poll('30s')
            ->emptyStateHeading('No alerts')
            ->emptyStateDescription('Alerts for your patients will appear here.');
    }
}