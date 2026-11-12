<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DbSchema;

class ClinicalAssessmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'clinicalAssessments';

    protected static ?string $title = 'Clinical assessments';

    // allow add and edit even though this is the View page
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $patient = $this->getOwnerRecord();

        return $schema
            ->columns(2)
            ->components([
                Select::make('assessment_type')
                    ->label('Assessment type')
                    ->options([
                        'initial' => 'Initial',
                        'progress' => 'Progress',
                        'mental_health' => 'Mental health',
                        'physical_health' => 'Physical health',
                        'discharge' => 'Discharge',
                    ])
                    ->default('initial')
                    ->required(),

                DateTimePicker::make('assessment_date')
                    ->label('Assessment date and time')
                    ->default(now())
                    ->seconds(false)
                    ->required(),

                Select::make('admission_id')
                    ->label('Related admission (optional)')
                    ->options(fn (): array => $patient->admissions()
                        ->orderByDesc('admission_date')
                        ->get()
                        ->mapWithKeys(fn ($admission): array => [
                            $admission->id => $admission->admission_number.' ('.ucfirst((string) $admission->status).')',
                        ])
                        ->all())
                    ->default(fn () => $patient->admissions()->where('status', 'admitted')->value('id'))
                    ->columnSpanFull(),

                Textarea::make('presenting_concerns')
                    ->label('Presenting concerns')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('clinical_findings')
                    ->label('Clinical findings')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('diagnosis')
                    ->label('Diagnosis')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull()
                    ->visible(fn (): bool => DbSchema::hasColumn('clinical_assessments', 'diagnosis')),

                Textarea::make('mental_health_notes')
                    ->label('Mental health notes')
                    ->rows(2),

                Textarea::make('physical_health_notes')
                    ->label('Physical health notes')
                    ->rows(2),

                Textarea::make('risk_assessment')
                    ->label('Risk assessment')
                    ->rows(2),

                Textarea::make('recommended_actions')
                    ->label('Recommended actions')
                    ->rows(2),

                DatePicker::make('next_assessment_date')
                    ->label('Next assessment date')
                    ->afterOrEqual('assessment_date'),
            ]);
    }

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
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('diagnosis')
                    ->label('Diagnosis')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('-')
                    ->visible(fn (): bool => DbSchema::hasColumn('clinical_assessments', 'diagnosis')),

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
            ->headerActions([
                CreateAction::make()
                    ->label('Add assessment')
                    ->modalHeading('Add clinical assessment')
                    ->mutateDataUsing(function (array $data): array {
                        $doctorId = auth()->user()?->doctor?->id;

                        abort_unless($doctorId, 403);

                        $data['doctor_id'] = $doctorId;

                        return $data;
                    }),
            ])
            // only the doctor who recorded it can edit it; there is no delete
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit clinical assessment')
                    ->visible(fn ($record): bool => (int) $record->doctor_id === (int) auth()->user()?->doctor?->id),
            ])
            ->emptyStateHeading('No clinical assessments yet');
    }
}