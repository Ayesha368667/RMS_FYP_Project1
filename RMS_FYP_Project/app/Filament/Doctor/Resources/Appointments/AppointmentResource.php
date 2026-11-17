<?php

namespace App\Filament\Doctor\Resources\Appointments;

use App\Filament\Doctor\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Doctor\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationLabel = 'Appointments';

    protected static string|UnitEnum|null $navigationGroup = 'Medicines and Appointments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    // a doctor sees only HIS OWN appointments
    public static function getEloquentQuery(): Builder
    {
        $doctorId = auth()->user()?->doctor?->id;

        $query = parent::getEloquentQuery();

        if (! $doctorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('doctor_id', $doctorId);
    }

    // everything is done with buttons on the list (approve, reject, schedule...)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
        ];
    }
}