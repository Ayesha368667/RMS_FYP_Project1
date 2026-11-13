<?php

namespace App\Filament\Admin\Resources\Patients;

use App\Filament\Admin\Resources\Patients\Pages\CreatePatient;
use App\Filament\Admin\Resources\Patients\Pages\EditPatient;
use App\Filament\Admin\Resources\Patients\Pages\ListPatients;
use App\Filament\Admin\Resources\Patients\Pages\ViewPatient;
use App\Filament\Admin\Resources\Patients\RelationManagers\AdmissionsRelationManager;
use App\Filament\Admin\Resources\Patients\RelationManagers\AlertsRelationManager;
use App\Filament\Admin\Resources\Patients\RelationManagers\DoctorAssignmentsRelationManager;
use App\Filament\Admin\Resources\Patients\RelationManagers\FamilyContactsRelationManager;
use App\Filament\Admin\Resources\Patients\RelationManagers\FollowupResponsesRelationManager;
use App\Filament\Admin\Resources\Patients\Schemas\PatientForm;
use App\Filament\Admin\Resources\Patients\Schemas\PatientInfolist;
use App\Filament\Admin\Resources\Patients\Tables\PatientsTable;

use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // Har patient ke saath uska user bhi ek hi query mein aa jaye
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdmissionsRelationManager::class,
            AlertsRelationManager::class,
            DoctorAssignmentsRelationManager::class,
            FamilyContactsRelationManager::class,
            FollowupResponsesRelationManager::class,
        ];
            
        
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view'   => ViewPatient::route('/{record}'),
            'edit'   => EditPatient::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}