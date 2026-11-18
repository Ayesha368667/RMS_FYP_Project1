<?php

namespace App\Filament\Admin\Resources\FamilyContactLogs;

use App\Filament\Admin\Resources\FamilyContactLogs\Pages\ListFamilyContactLogs;
use App\Filament\Admin\Resources\FamilyContactLogs\Tables\FamilyContactLogsTable;
use App\Models\FamilyContactLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FamilyContactLogResource extends Resource
{
    protected static ?string $model = FamilyContactLog::class;

    protected static ?string $navigationLabel = 'Family Contact Logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 3;

    // Patient, family member aur contacted_by ek hi query mein aa jayein
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient.user', 'familyContact', 'contactedBy']);
    }

    // Create sirf "Add Manual Contact" button se hoga
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return FamilyContactLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFamilyContactLogs::route('/'),
        ];
    }
}