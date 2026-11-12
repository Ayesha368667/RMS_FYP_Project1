<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubstanceUseHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'substanceUseHistories';

    protected static ?string $title = 'Substance use history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('substance_name')
            ->modifyQueryUsing(fn ($query) => $query->with('recordedBy'))
            ->columns([
                TextColumn::make('substance_name')
                    ->label('Substance')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('substance_category')
                    ->label('Category')
                    ->badge()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('age_at_first_use')
                    ->label('Age at first use')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('duration_of_use')
                    ->label('Duration')
                    ->placeholder('-'),

                TextColumn::make('frequency_of_use')
                    ->label('Frequency')
                    ->placeholder('-'),

                TextColumn::make('route_of_use')
                    ->label('Route')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_use_date')
                    ->label('Last use')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('previous_treatment_history')
                    ->label('Previous treatment')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notes')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('recorded_at')
                    ->label('Recorded on')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('No substance use history recorded');
    }
}