<?php

namespace App\Filament\Admin\Resources\Alerts\Tables;

use App\Models\Alert;
use App\Models\Patient;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('triggered_at')
                    ->label('Triggered At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('alert_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('severity')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'critical', 'high' => 'danger',
                            'medium' => 'warning',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'open' => 'danger',
                            'under_action' => 'warning',
                            'resolved' => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('trigger_reason')
                    ->label('Reason')
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('missed_count')
                    ->label('Missed Days')
                    ->placeholder('-'),

                // raised_by khali ho to alert system ne banaya hai
                TextColumn::make('raisedBy.name')
                    ->label('Raised By')
                    ->placeholder('System'),
            ])
            ->defaultSort('triggered_at', 'desc')
            ->filters([

                // Pehli dafa sirf open alerts dikhengi
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

                // Type ki list database se khud banti hai
                SelectFilter::make('alert_type')
                    ->label('Type')
                    ->options(function () {
                        $types = Alert::query()->distinct()->pluck('alert_type');
                        $options = [];

                        foreach ($types as $type) {
                            $options[$type] = ucfirst(str_replace('_', ' ', $type));
                        }

                        return $options;
                    }),

                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->searchable()
                    ->options(function () {
                        return Patient::with('user')->get()->pluck('user.name', 'id');
                    }),

                Filter::make('triggered_range')
                    ->schema([
                        DatePicker::make('from')->label('Triggered from'),
                        DatePicker::make('until')->label('Triggered until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('triggered_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('triggered_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                    ViewAction::make(),
            ]);
    }
}