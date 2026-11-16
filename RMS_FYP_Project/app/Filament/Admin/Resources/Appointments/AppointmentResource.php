<?php

namespace App\Filament\Admin\Resources\Appointments;

use App\Filament\Admin\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Admin\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    // Patient aur doctor ka naam ek hi query mein aa jaye
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient.user', 'doctor.user']);
    }

    // Admin naya appointment yahan se nahi banayega
    public static function canCreate(): bool
    {
        return false;
    }

    // Sidebar mein pending requests ka number
    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::where('status', 'requested')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
        ];
    }
}