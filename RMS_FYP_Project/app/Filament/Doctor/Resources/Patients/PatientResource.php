<?php

namespace App\Filament\Doctor\Resources\Patients;

use App\Filament\Doctor\Resources\Patients\Pages\CreatePatient;
use App\Filament\Doctor\Resources\Patients\Pages\EditPatient;
use App\Filament\Doctor\Resources\Patients\Pages\ListPatients;
use App\Filament\Doctor\Resources\Patients\Schemas\PatientForm;
use App\Filament\Doctor\Resources\Patients\Tables\PatientsTable;
use App\Models\Patient;
use App\Models\Doctor;

use BackedEnum;
use UnitEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
protected static string|UnitEnum|null $navigationGroup = 'Patients';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
        public static function getEloquentQuery(): Builder
    {
        $doctorId = \App\Models\Doctor::where('user_id', auth()->id())->value('id');
    
    if (!$doctorId) {
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    return parent::getEloquentQuery()->whereHas('doctorAssignments', function ($query) use ($doctorId) {
        $query->where('doctor_id', $doctorId)->where('status', 'active');
    });
    }

}
