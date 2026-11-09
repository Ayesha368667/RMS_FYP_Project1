<?php

namespace App\Filament\Admin\Resources\Admissions;

use App\Filament\Admin\Resources\Admissions\Pages\CreateAdmission;
use App\Filament\Admin\Resources\Admissions\Pages\EditAdmission;
use App\Filament\Admin\Resources\Admissions\Pages\ListAdmissions;
use App\Filament\Admin\Resources\Admissions\Schemas\AdmissionForm;
use App\Filament\Admin\Resources\Admissions\Tables\AdmissionsTable;
use App\Models\Admission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdmissionResource extends Resource
{
    protected static ?string $model = Admission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'admission_number';

    protected static string|\UnitEnum|null $navigationGroup = 'Patient care';
    public static function form(Schema $schema): Schema
    {
        return AdmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdmissionsTable::configure($table);
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
            'index' => ListAdmissions::route('/'),
            'create' => CreateAdmission::route('/create'),
            'edit' => EditAdmission::route('/{record}/edit'),
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
