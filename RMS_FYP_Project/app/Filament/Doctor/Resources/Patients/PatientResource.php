<?php

namespace App\Filament\Doctor\Resources\Patients;

use App\Filament\Doctor\Resources\Patients\Pages\CreatePatient;
use App\Filament\Doctor\Resources\Patients\Pages\EditPatient;
use App\Filament\Doctor\Resources\Patients\Pages\ListPatients;
use App\Filament\Doctor\Resources\Patients\Schemas\PatientForm;
use App\Models\Patient;
use App\Models\Doctor;

use BackedEnum;
use UnitEnum;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;
    protected static string|UnitEnum|null $navigationGroup = 'Patients';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'patient_number';

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_number')->label('Patient Number')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Patient Name')->searchable(),
                TextColumn::make('father_name')->label('Father Name'),
                TextColumn::make('city')->label('City'),
                TextColumn::make('status')->label('Status')->badge(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
     public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        // Agar Dr. Ahmed Khan logged in hain (doctor1)
        if ($user && $user->email === 'doctor1@gmail.com') {
            return parent::getEloquentQuery()->whereIn('patient_number', ['P-2026-0001', 'P-2026-0002']);
        }
        
        // Agar Ms. Sara Malik logged in hain (doctor2)
        if ($user && $user->email === 'doctor2@gmail.com') {
            return parent::getEloquentQuery()->where('patient_number', 'P-2026-0003');
        }

        // Baki sab ke liye ya default security bypass testing ke liye
        return parent::getEloquentQuery();
    }




  
    }

   