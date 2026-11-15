<?php

namespace App\Filament\Doctor\Resources\Patients\RelationManagers;
use App\Filament\Doctor\Resources\TreatmentPlans\TreatmentPlanResource;

use App\Services\TreatmentPlanService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class TreatmentPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'treatmentPlans';

    protected static ?string $title = 'Treatment plans';

    // allow create, edit and cancel even though this is the View page
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
                TextInput::make('plan_title')
                    ->label('Plan title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

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

                Textarea::make('goals')
                    ->label('Goals')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('planned_interventions')
                    ->label('Planned interventions')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('start_date')
                    ->label('Start date')
                    ->default(now())
                    ->required(),

                DatePicker::make('target_end_date')
                    ->label('Target end date')
                    ->afterOrEqual('start_date'),

                // only shown when editing an existing plan, saved in the version history
                Textarea::make('change_reason')
                    ->label('Reason for this change (saved in version history)')
                    ->rows(2)
                    ->columnSpanFull()
                    ->visible(fn (?Model $record): bool => $record !== null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plan_title')
            ->modifyQueryUsing(fn ($query) => $query->with('createdByDoctor.user'))
            ->columns([
                TextColumn::make('plan_title')
                    ->label('Plan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('target_end_date')
                    ->label('Target end')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('versions_count')
                    ->label('Versions')
                    ->counts('versions'),

                TextColumn::make('createdByDoctor.user.name')
                    ->label('Created by')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('goals')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('planned_interventions')
                    ->label('Interventions')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create plan')
                    ->modalHeading('Create treatment plan (version 1)')
                    ->before(function (CreateAction $action, $livewire): void {
                        $hasActivePlan = $livewire->getOwnerRecord()
                            ->treatmentPlans()
                            ->where('status', 'active')
                            ->exists();

                        if ($hasActivePlan) {
                            Notification::make()
                                ->title('This patient already has an active treatment plan. Complete or cancel it first.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->using(function (array $data, $livewire): Model {
                        return app(TreatmentPlanService::class)->create($livewire->getOwnerRecord(), $data);
                    }),
            ])
            // no delete: a plan is only cancelled, never removed
            ->recordActions([
                                Action::make('versions')
                    ->label('Version history')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn ($record): string => TreatmentPlanResource::getUrl('view', ['record' => $record])),

                Action::make('complete')
                    ->label('Mark completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->modalHeading('Mark treatment plan as completed')
                    ->modalDescription('The patient will be notified. This cannot be undone.')
                    ->modalSubmitActionLabel('Mark completed')
                    ->visible(fn ($record): bool => $record->status === 'active')
                    ->schema([
                        Textarea::make('note')
                            ->label('Completion note (optional)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data): void {
                        try {
                            app(TreatmentPlanService::class)->complete($record, $data['note'] ?? null);

                            Notification::make()
                                ->title('Treatment plan marked as completed')
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->modalHeading('Edit treatment plan')
                    ->visible(fn ($record): bool => in_array($record->status, ['draft', 'active'], true))
                    ->using(function (Model $record, array $data): Model {
                        return app(TreatmentPlanService::class)->update($record, $data);
                    }),

                Action::make('cancel')
                    ->label('Cancel plan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalHeading('Cancel treatment plan')
                    ->modalDescription('The patient will be notified. A reason is required.')
                    ->modalSubmitActionLabel('Cancel plan')
                    ->visible(fn ($record): bool => in_array($record->status, ['draft', 'active'], true))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for cancelling')
                            ->required()
                            ->minLength(5)
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        try {
                            app(TreatmentPlanService::class)->cancel($record, $data['reason']);

                            Notification::make()
                                ->title('Treatment plan cancelled')
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No treatment plans yet');
    }
}