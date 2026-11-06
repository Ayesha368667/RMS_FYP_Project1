<?php

namespace App\Filament\Admin\Resources\DoctorAssignments;

use App\Filament\Admin\Resources\DoctorAssignments\Pages\CreateDoctorAssignment;
use App\Filament\Admin\Resources\DoctorAssignments\Pages\EditDoctorAssignment;
use App\Filament\Admin\Resources\DoctorAssignments\Pages\ListDoctorAssignments;
use App\Filament\Admin\Resources\DoctorAssignments\Schemas\DoctorAssignmentForm;
use App\Filament\Admin\Resources\DoctorAssignments\Tables\DoctorAssignmentsTable;
use App\Models\DoctorAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DoctorAssignmentResource extends Resource
{
    protected static ?string $model = DoctorAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

protected static string|\UnitEnum|null $navigationGroup = 'Patient care';

    public static function form(Schema $schema): Schema
    {
        return DoctorAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoctorAssignmentsTable::configure($table);
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
            'index' => ListDoctorAssignments::route('/'),
            'create' => CreateDoctorAssignment::route('/create'),
            'edit' => EditDoctorAssignment::route('/{record}/edit'),
        ];
    }
}
