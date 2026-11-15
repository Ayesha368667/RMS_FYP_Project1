<?php

namespace App\Filament\Doctor\Resources\Patients;

use App\Filament\Doctor\Resources\Patients\Pages\CreatePatient;
use App\Filament\Doctor\Resources\Patients\Pages\EditPatient;
use App\Filament\Doctor\Resources\Patients\Pages\ListPatients;
use App\Filament\Doctor\Resources\Patients\Pages\ViewPatient;
use App\Filament\Doctor\Resources\Patients\RelationManagers\AdmissionsRelationManager;
use App\Filament\Doctor\Resources\Patients\RelationManagers\ClinicalAssessmentsRelationManager;
use App\Filament\Doctor\Resources\Patients\RelationManagers\SubstanceUseHistoriesRelationManager;
use App\Filament\Doctor\Resources\Patients\Schemas\PatientForm;
use App\Filament\Doctor\Resources\Patients\Tables\PatientsTable;
use App\Filament\Doctor\Resources\Patients\RelationManagers\TreatmentPlansRelationManager;
use App\Models\Patient;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationLabel = 'Patients';

    protected static ?string $pluralModelLabel = 'Patients';

    protected static ?string $modelLabel = 'patient';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static ?string $recordTitleAttribute = 'patient_number';

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

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
                            ->state(function ($record) {
                                if ($record->date_of_birth) {
                                    return $record->date_of_birth->age . ' yrs';
                                }

                                return '-';
                            }),

                        TextEntry::make('gender')
                            ->formatStateUsing(function ($state) {
                                return ucfirst((string) $state);
                            })
                            ->placeholder('-'),

                        TextEntry::make('city')
                            ->placeholder('-'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(function ($state) {
                                return match ($state) {
                                    'admitted' => 'success',
                                    'registered' => 'info',
                                    'inactive' => 'danger',
                                    default => 'gray',
                                };
                            })
                            ->formatStateUsing(function ($state) {
                                return ucfirst((string) $state);
                            }),
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
        TreatmentPlansRelationManager::class,
        RelationGroup::make('History', [
            SubstanceUseHistoriesRelationManager::class,
            ClinicalAssessmentsRelationManager::class,
        ]),
    ];
}
    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}