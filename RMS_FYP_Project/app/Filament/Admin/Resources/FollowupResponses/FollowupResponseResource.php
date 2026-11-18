<?php

namespace App\Filament\Admin\Resources\FollowupResponses;

use App\Filament\Admin\Resources\FollowupResponses\Pages\ListFollowupResponses;
use App\Filament\Admin\Resources\FollowupResponses\Tables\FollowupResponsesTable;
use App\Models\FollowupResponse;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class FollowupResponseResource extends Resource
{
    protected static ?string $model = FollowupResponse::class;

    protected static ?string $navigationLabel = 'Follow-up Monitoring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring';
    protected static ?int $navigationSort = 1;

    // Patient ka naam ek hi query mein aa jaye
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['patient.user']);
    }

    // Admin sirf dekhega, banayega nahi
    public static function canCreate(): bool
    {
        return false;
    }

        // Sidebar mein naye missed follow-ups ka number (last visit ke baad ke)
    public static function getNavigationBadge(): ?string
    {
        $query = FollowupResponse::whereNull('submitted_at')
            ->whereDate('due_date', '<', today());

        $lastSeen = Cache::get('followups_last_seen_' . auth()->id());

        if ($lastSeen) {
            $query->whereDate('due_date', '>=', Carbon::parse($lastSeen)->toDateString());
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
        return FollowupResponsesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFollowupResponses::route('/'),
        ];
    }
}