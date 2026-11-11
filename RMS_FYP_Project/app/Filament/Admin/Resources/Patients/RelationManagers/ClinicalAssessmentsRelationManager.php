<?php

namespace App\Filament\Doctor\Resources\Patients\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicalAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'clinicalAssessments';

    protected static ?string $title = 'Clinical assessments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('assessment_type')
            ->modifyQueryUsing(fn ($query) => $query->with('doctor.user'))
            ->columns([
                TextColumn::make('assessment_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('assessment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                TextColumn::make('doctor.user.name')
                    ->label('Assessed by')
                    ->placeholder('-'),

                TextColumn::make('clinical_findings')
                    ->label('Findings')
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('presenting_concerns')
                    ->label('Presenting concerns')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('mental_health_notes')
                    ->label('Mental health notes')
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('physical_health_notes')
                    ->label('Physical health notes')
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('risk_assessment')
                    ->label('Risk')
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('recommended_actions')
                    ->label('Recommended actions')
                    ->limit(60)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('next_assessment_date')
                    ->label('Next assessment')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('assessment_date', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('No clinical assessments yet');
    }
}