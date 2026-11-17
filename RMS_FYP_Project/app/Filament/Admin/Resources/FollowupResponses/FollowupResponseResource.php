<?php

namespace App\Filament\Admin\Resources\FollowupResponses;

use App\Filament\Admin\Resources\FollowupResponses\Pages\ListFollowupResponses;
use App\Filament\Admin\Resources\FollowupResponses\Tables\FollowupResponsesTable;
use App\Models\FollowupResponse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FollowupResponseResource extends Resource
{
    protected static ?string $model = FollowupResponse::class;

    protected static ?string $navigationLabel = 'Follow-up Monitoring';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

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

    // Sidebar mein missed follow-ups ka number
    public static function getNavigationBadge(): ?string
    {
        $count = FollowupResponse::whereNull('submitted_at')
            ->whereDate('due_date', '<', today())
            ->count();

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