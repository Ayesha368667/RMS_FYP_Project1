<?php

namespace App\Filament\Admin\Resources\Alerts;

use App\Filament\Admin\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Admin\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    // Patient aur raised_by ka naam ek hi query mein aa jaye
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient.user', 'raisedBy']);
    }

    // Create sirf "Create Manual Alert" button se hoga
    public static function canCreate(): bool
    {
        return false;
    }

        // Sidebar mein naye open alerts ka number (last visit ke baad ke)
    public static function getNavigationBadge(): ?string
    {
        $query = Alert::where('status', 'open');

        $lastSeen = Cache::get('alerts_last_seen_' . auth()->id());

        if ($lastSeen) {
            $query->where('triggered_at', '>', $lastSeen);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlerts::route('/'),
        ];
    }
}