<?php

namespace App\Filament\Doctor\Resources\Patients;

use App\Filament\Doctor\Resources\Patients\Pages\ListPatients;
use App\Filament\Doctor\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
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

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
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

    // this list is read-only for doctors
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
        ];
    }
}