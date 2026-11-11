<?php

namespace App\Filament\Doctor\Resources\Patients;

use App\Filament\Doctor\Resources\Patients\Pages\ListPatients;
use App\Filament\Doctor\Resources\Patients\Pages\ViewPatient;
use App\Filament\Doctor\Resources\Patients\RelationManagers\AdmissionsRelationManager;
use App\Filament\Doctor\Resources\Patients\RelationManagers\ClinicalAssessmentsRelationManager;
use App\Filament\Doctor\Resources\Patients\RelationManagers\SubstanceUseHistoriesRelationManager;
use App\Filament\Doctor\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationLabel = 'My patients';

    protected static ?string $pluralModelLabel = 'My patients';

    protected static ?string $modelLabel = 'patient';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Patients';

    protected static ?string $recordTitleAttribute = 'patient_number';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient profile')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('patient_number')
                            ->label('Patient ID')
                            ->copyable(),

                        TextEntry::make('user.name')
                            ->label('Name'),

                        TextEntry::make('father_name')
                            ->label('Father name')
                            ->placeholder('-'),

                        TextEntry::make('age')
                            ->label('Age')
                            ->state(fn ($record): string => $record->date_of_birth
                                ? $record->date_of_birth->age.' yrs'
                                : '-'),

                        TextEntry::make('gender')
                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                            ->placeholder('-'),

                        TextEntry::make('city')
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'admitted' => 'success',
                                'registered' => 'info',
                                'inactive' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdmissionsRelationManager::class,
            RelationGroup::make('History', [
                SubstanceUseHistoriesRelationManager::class,
                ClinicalAssessmentsRelationManager::class,
            ]),
        ];
    }

    /**
     * A doctor only sees patients that have an ACTIVE assignment with that doctor.
     */
    public static function getEloquentQuery(): Builder
    {
        $doctorId = auth()->user()?->doctor?->id;

        $query = parent::getEloquentQuery()->with(['user', 'doctorAssignments']);

        if (! $doctorId) {
            // a user without a doctor profile sees nothing
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('doctorAssignments', function ($assignments) use ($doctorId) {
            $assignments
                ->where('doctor_id', $doctorId)
                ->where('status', 'active');
        });
    }

    // this resource is read-only for doctors
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'view' => ViewPatient::route('/{record}'),
        ];
    }
}