<?php

namespace App\Filament\Admin\Resources\Medicines;

use App\Filament\Admin\Resources\Medicines\Pages\CreateMedicine;
use App\Filament\Admin\Resources\Medicines\Pages\EditMedicine;
use App\Filament\Admin\Resources\Medicines\Pages\ListMedicines;
use App\Filament\Admin\Resources\Medicines\Schemas\MedicineForm;
use App\Filament\Admin\Resources\Medicines\Tables\MedicinesTable;
use App\Models\Medicine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MedicineResource extends Resource
{
    protected static ?string $model = Medicine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // (a) Record title attribute ko 'name' kar diya hai
    protected static ?string $recordTitleAttribute = 'name';

    // (b) Navigation group add kar diya hai
    protected static string|\UnitEnum|null $navigationGroup = 'Master data';

    public static function form(Schema $schema): Schema
    {
        return MedicineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicinesTable::configure($table);
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
            'index' => ListMedicines::route('/'),
            'create' => CreateMedicine::route('/create'),
            'edit' => EditMedicine::route('/{record}/edit'),
        ];
    }
}