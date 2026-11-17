<?php

namespace App\Filament\Doctor\Resources\Patients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TherapySessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'therapySessions';

    protected static ?string $title = 'Therapy sessions';

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
                DateTimePicker::make('session_date')
                    ->label('Session date and time')
                    ->default(now())
                    ->seconds(false)
                    ->required(),

                Select::make('session_type')
                    ->label('Session type')
                    ->options([
                        'individual' => 'Individual',
                        'group' => 'Group',
                        'counselling' => 'Counselling',
                        'other' => 'Other',
                    ])
                    ->required(),

                Select::make('session_status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'missed' => 'Missed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('completed')
                    ->required(),

                Select::make('treatment_plan_id')
                    ->label('Treatment plan (optional)')
                    ->options(fn (): array => $patient->treatmentPlans()
                        ->orderByDesc('start_date')
                        ->pluck('plan_title', 'id')
                        ->all())
                    ->default(fn () => $patient->treatmentPlans()->where('status', 'active')->value('id')),

                Textarea::make('session_notes')
                    ->label('Session notes (confidential, not shown to the patient)')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('progress_summary')
                    ->label('Progress summary')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('next_steps')
                    ->label('Next steps')
                    ->rows(2)
                    ->columnSpanFull(),

                DateTimePicker::make('next_session_date')
                    ->label('Next session (optional)')
                    ->seconds(false)
                    ->after('session_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('session_date')
            ->modifyQueryUsing(fn ($query) => $query->with(['recordedBy', 'treatmentPlan']))
            ->columns([
                TextColumn::make('session_date')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('session_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('session_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'scheduled' => 'info',
                        'missed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('progress_summary')
                    ->label('Progress')
                    ->limit(60)
                    ->placeholder('-'),

                TextColumn::make('next_session_date')
                    ->label('Next session')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('session_date', 'desc')
            ->filters([
                SelectFilter::make('session_status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'missed' => 'Missed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add session')
                    ->modalHeading('Record therapy session')
                    ->before(function (CreateAction $action): void {
                        if (! $this->myAssignmentId()) {
                            Notification::make()
                                ->title('You are not assigned to this patient.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->mutateDataUsing(function (array $data): array {
                        // linked to this doctor's ACTIVE assignment for the patient
                        $data['doctor_assignment_id'] = $this->myAssignmentId();
                        $data['recorded_by'] = auth()->id();

                        return $data;
                    }),
            ])
            // no delete: saved sessions are never removed (FR8)
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Edit therapy session'),
            ])
            ->emptyStateHeading('No therapy sessions yet');
    }

    private function myAssignmentId(): ?int
    {
        $doctorId = auth()->user()?->doctor?->id;

        return $this->getOwnerRecord()
            ->doctorAssignments()
            ->where('doctor_id', $doctorId)
            ->where('status', 'active')
            ->value('id');
    }
}