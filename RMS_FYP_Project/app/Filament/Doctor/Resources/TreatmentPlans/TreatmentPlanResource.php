<?php

namespace App\Filament\Doctor\Resources\TreatmentPlans;

use App\Filament\Doctor\Resources\TreatmentPlans\Pages\ViewTreatmentPlan;
use App\Filament\Doctor\Resources\TreatmentPlans\RelationManagers\VersionsRelationManager;
use App\Models\TreatmentPlan;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TreatmentPlanResource extends Resource
{
    protected static ?string $model = TreatmentPlan::class;

    protected static ?string $modelLabel = 'treatment plan';

    protected static ?string $recordTitleAttribute = 'plan_title';

    // opened from the patient profile, not from the sidebar
    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Treatment plan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('plan_title')
                            ->label('Plan'),

                        TextEntry::make('patient.user.name')
                            ->label('Patient'),

                        TextEntry::make('patient.patient_number')
                            ->label('Patient ID'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'active' => 'success',
                                'draft' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                        TextEntry::make('start_date')
                            ->label('Start date')
                            ->date(),

                        TextEntry::make('target_end_date')
                            ->label('Target end date')
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('createdByDoctor.user.name')
                            ->label('Created by')
                            ->placeholder('-'),

                        TextEntry::make('goals')
                            ->columnSpanFull(),

                        TextEntry::make('planned_interventions')
                            ->label('Planned interventions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    /**
     * A doctor only sees plans of patients that have an ACTIVE assignment with that doctor.
     */
    public static function getEloquentQuery(): Builder
    {
        $doctorId = auth()->user()?->doctor?->id;

        $query = parent::getEloquentQuery()->with(['patient.user', 'createdByDoctor.user']);

        if (! $doctorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('patient.doctorAssignments', function ($assignments) use ($doctorId) {
            $assignments
                ->where('doctor_id', $doctorId)
                ->where('status', 'active');
        });
    }

    // read-only: plans are created and changed only from the patient profile tab
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'view' => ViewTreatmentPlan::route('/{record}'),
        ];
    }
}