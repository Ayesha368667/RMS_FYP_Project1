<?php

namespace App\Filament\Doctor\Resources\TreatmentPlans\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version history';

    private const FIELD_LABELS = [
        'plan_title' => 'Title',
        'admission_id' => 'Admission',
        'goals' => 'Goals',
        'planned_interventions' => 'Interventions',
        'start_date' => 'Start date',
        'target_end_date' => 'Target end date',
        'status' => 'Status',
    ];

    private static function snapshot($record, string $key)
    {
        return data_get($record->plan_snapshot, $key);
    }

    private static function snapshotDate($record, string $key): string
    {
        $value = self::snapshot($record, $key);

        return $value ? Carbon::parse($value)->format('d M Y') : '-';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version_number')
            ->modifyQueryUsing(fn ($query) => $query->with('changedBy'))
            ->columns([
                TextColumn::make('version_number')
                    ->label('Version')
                    ->prefix('v')
                    ->sortable(),

                TextColumn::make('changed_at')
                    ->label('Changed on')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('changedBy.name')
                    ->label('Changed by')
                    ->placeholder('-'),

                TextColumn::make('change_reason')
                    ->label('Reason')
                    ->wrap(),

                TextColumn::make('what_changed')
                    ->label('What changed')
                    ->wrap()
                    ->state(function ($record): string {
                        $previous = $record->treatmentPlan
                            ->versions()
                            ->where('version_number', '<', $record->version_number)
                            ->orderByDesc('version_number')
                            ->first();

                        if (! $previous) {
                            return 'First version';
                        }

                        $changed = [];

                        foreach (self::FIELD_LABELS as $key => $label) {
                            if (self::snapshot($record, $key) != self::snapshot($previous, $key)) {
                                $changed[] = $label;
                            }
                        }

                        return $changed ? implode(', ', $changed) : 'No field changed';
                    }),

                TextColumn::make('snapshot_status')
                    ->label('Status then')
                    ->badge()
                    ->state(fn ($record): string => ucfirst((string) self::snapshot($record, 'status')))
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('snapshot_title')
                    ->label('Title then')
                    ->state(fn ($record): string => (string) self::snapshot($record, 'plan_title'))
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('snapshot_goals')
                    ->label('Goals then')
                    ->state(fn ($record): string => (string) self::snapshot($record, 'goals'))
                    ->limit(70)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('snapshot_interventions')
                    ->label('Interventions then')
                    ->state(fn ($record): string => (string) self::snapshot($record, 'planned_interventions'))
                    ->limit(70)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('snapshot_start')
                    ->label('Start then')
                    ->state(fn ($record): string => self::snapshotDate($record, 'start_date'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('snapshot_end')
                    ->label('Target end then')
                    ->state(fn ($record): string => self::snapshotDate($record, 'target_end_date'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('version_number', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('No versions yet');
    }
}