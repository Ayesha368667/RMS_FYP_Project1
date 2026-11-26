<?php

namespace App\Filament\Admin\Resources\FollowupResponses\Tables;

use App\Models\FollowupResponse;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowupResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not submitted'),

                // Status calculate hota hai: submitted_at aur due_date se
                TextColumn::make('followup_state')
                    ->label('Status')
                    ->badge()
                    ->state(function (FollowupResponse $record) {
                        if ($record->submitted_at !== null) {
                            return 'Responded';
                        }

                        if ($record->due_date->lt(today())) {
                            return 'Missed';
                        }

                        return 'Pending';
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'Responded' => 'success',
                            'Missed' => 'danger',
                            default => 'warning',
                        };
                    }),

                IconColumn::make('is_critical')
                    ->label('Critical')
                    ->boolean(),

                TextColumn::make('mood_score')
                    ->label('Mood')
                    ->placeholder('-'),

                TextColumn::make('craving_level')
                    ->label('Craving')
                    ->placeholder('-'),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([

                // Status filter
                SelectFilter::make('followup_state')
                    ->label('Status')
                    ->options([
                        'responded' => 'Responded',
                        'missed' => 'Missed',
                        'pending' => 'Pending',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === 'responded') {
                            return $query->whereNotNull('submitted_at');
                        }

                        if ($value === 'missed') {
                            return $query->whereNull('submitted_at')
                                ->whereDate('due_date', '<', today());
                        }

                        if ($value === 'pending') {
                            return $query->whereNull('submitted_at')
                                ->whereDate('due_date', '>=', today());
                        }

                        return $query;
                    }),

                // Patient filter
                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->searchable()
                    ->options(function () {
                        return Patient::with('user')->get()->pluck('user.name', 'id');
                    }),

                // Date range filter
                Filter::make('due_date_range')
                    ->schema([
                        DatePicker::make('from')->label('Due from'),
                        DatePicker::make('until')->label('Due until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('due_date', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('due_date', '<=', $data['until']);
                        }

                        return $query;
                    }),

                // Sirf critical
                Filter::make('critical')
                    ->label('Critical only')
                    ->toggle()
                    ->query(function (Builder $query) {
                        return $query->where('is_critical', true);
                    }),
            ]);
    }
}