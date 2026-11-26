<?php

namespace App\Filament\Admin\Resources\FamilyContactLogs\Tables;

use App\Models\FamilyContactLog;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamilyContactLogsTable
{
    // Filter ki list database ki maujooda values se banti hai
    private static function optionsFor(string $column): array
    {
        $values = FamilyContactLog::query()->distinct()->pluck($column);
        $options = [];

        foreach ($values as $value) {
            $options[$value] = ucfirst(str_replace('_', ' ', $value));
        }

        return $options;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contacted_at')
                    ->label('Contacted At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('patient.user.name')
                    ->label('Patient')
                    ->searchable(),

                TextColumn::make('familyContact.contact_name')
                    ->label('Family Member'),

                TextColumn::make('familyContact.relationship')
                    ->label('Relation')
                    ->formatStateUsing(function (string $state) {
                        return ucfirst($state);
                    }),

                TextColumn::make('familyContact.phone')
                    ->label('Phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('contact_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return strtoupper($state);
                    }),

                TextColumn::make('outcome')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'answered', 'sent', 'delivered' => 'success',
                            'failed', 'wrong_number' => 'danger',
                            'pending', 'no_answer', 'busy' => 'warning',
                            default => 'gray',
                        };
                    }),

                // contacted_by khali ho to system ne SMS bheji hai
                TextColumn::make('contactedBy.name')
                    ->label('Contacted By')
                    ->placeholder('System'),

                TextColumn::make('alert_id')
                    ->label('Alert')
                    ->formatStateUsing(function ($state) {
                        return '#' . $state;
                    })
                    ->placeholder('-'),

                TextColumn::make('remarks')
                    ->limit(50)
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('message')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('contacted_at', 'desc')
            ->filters([

                SelectFilter::make('patient_id')
                    ->label('Patient')
                    ->searchable()
                    ->options(function () {
                        return Patient::with('user')->get()->pluck('user.name', 'id');
                    }),

                SelectFilter::make('outcome')
                    ->options(function () {
                        return self::optionsFor('outcome');
                    }),

                SelectFilter::make('channel')
                    ->options(function () {
                        return self::optionsFor('channel');
                    }),

                SelectFilter::make('contact_type')
                    ->label('Type')
                    ->options(function () {
                        return self::optionsFor('contact_type');
                    }),

                Filter::make('contacted_range')
                    ->schema([
                        DatePicker::make('from')->label('Contacted from'),
                        DatePicker::make('until')->label('Contacted until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('contacted_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('contacted_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ]);
    }
}