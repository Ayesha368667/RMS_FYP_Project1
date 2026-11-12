<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubstanceUseHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'substanceUseHistories';

    protected static ?string $title = 'Substance use history';

    // allow add and edit even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('substance_name')
                    ->label('Substance')
                    ->required()
                    ->maxLength(100),

                Select::make('substance_category')
                    ->label('Category')
                    ->options([
                        'Opioid' => 'Opioid',
                        'Stimulant' => 'Stimulant',
                        'Cannabis' => 'Cannabis',
                        'Alcohol' => 'Alcohol',
                        'Sedative' => 'Sedative',
                        'Inhalant' => 'Inhalant',
                        'Other' => 'Other',
                    ])
                    ->searchable(),

                TextInput::make('age_at_first_use')
                    ->label('Age at first use')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100),

                TextInput::make('duration_of_use')
                    ->label('Duration of use')
                    ->placeholder('e.g. 5 years')
                    ->maxLength(100),

                Select::make('frequency_of_use')
                    ->label('Frequency of use')
                    ->options([
                        'Daily' => 'Daily',
                        'Several times a week' => 'Several times a week',
                        'Weekly' => 'Weekly',
                        'Occasionally' => 'Occasionally',
                    ]),

                DatePicker::make('last_use_date')
                    ->label('Last use')
                    ->maxDate(now()),

                Select::make('route_of_use')
                    ->label('Route of use')
                    ->options([
                        'Oral' => 'Oral',
                        'Smoking' => 'Smoking',
                        'Injection' => 'Injection',
                        'Inhalation' => 'Inhalation',
                        'Snorting' => 'Snorting',
                        'Other' => 'Other',
                    ]),

                Textarea::make('previous_treatment_history')
                    ->label('Previous treatment')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

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
            ->headerActions([
                CreateAction::make()
                    ->label('Add substance history')
                    ->modalHeading('Add substance use history')
                    ->mutateDataUsing(function (array $data): array {
                        $data['recorded_by'] = auth()->id();
                        $data['recorded_at'] = now();

                        return $data;
                    }),
            ])
            // only the user who recorded it can edit it; there is no delete
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit substance use history')
                    ->visible(fn ($record): bool => (int) $record->recorded_by === (int) auth()->id()),
            ])
            ->emptyStateHeading('No substance use history recorded');
    }
}