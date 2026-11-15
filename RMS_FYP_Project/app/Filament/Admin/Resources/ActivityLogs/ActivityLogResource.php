<?php

namespace App\Filament\Admin\Resources\ActivityLogs;

use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Admin\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Models\ActivityLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationLabel = 'Activity logs';

    protected static ?string $modelLabel = 'activity log';

    protected static ?string $pluralModelLabel = 'Activity logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 100;

    // ---- Read-only: koi bhi na banaye, na badle, na mitaye ----
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

    // Har log ke saath uska user ek hi query mein aa jaye
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    // ---- Details page (View) ----
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Activity details')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('created_at')->label('When')->dateTime('d M Y, h:i:s A'),
                    TextEntry::make('user.name')->label('Done by')->placeholder('System'),
                    TextEntry::make('action')->badge(),
                    TextEntry::make('subject_type')->label('Record type'),
                    TextEntry::make('subject_id')->label('Record ID'),
                    TextEntry::make('ip_address')->label('IP address')->placeholder('-'),
                    TextEntry::make('description')->columnSpanFull(),
                ]),

            Section::make('Changes')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('old_values')
                        ->label('Old values')
                        ->state(function ($record) {
                            return self::pretty($record->old_values);
                        })
                        ->extraAttributes(['style' => 'white-space: pre-wrap; font-family: monospace;']),

                    TextEntry::make('new_values')
                        ->label('New values')
                        ->state(function ($record) {
                            return self::pretty($record->new_values);
                        })
                        ->extraAttributes(['style' => 'white-space: pre-wrap; font-family: monospace;']),
                ]),
        ]);
    }

    // Values ko saaf shakal mein dikhane ke liye
    private static function pretty($values)
    {
        if (empty($values)) {
            return '-';
        }

        return json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // ---- List ----
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable(),

                TextColumn::make('action')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'created', 'activated'  => 'success',
                            'updated'               => 'info',
                            'deactivated', 'deleted' => 'danger',
                            default                 => 'gray',
                        };
                    })
                    ->searchable(),

                TextColumn::make('subject_type')->label('Record type')->searchable(),
                TextColumn::make('subject_id')->label('Record ID'),
                TextColumn::make('description')->limit(60)->searchable(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchPlaceholder('Search user, action, record or description')
            ->filters([

                // 1. Action se (created, updated, deactivated ...)
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(function () {
                        return ActivityLog::query()
                            ->distinct()
                            ->orderBy('action')
                            ->pluck('action', 'action')
                            ->toArray();
                    }),

                // 2. User se (kis ne kiya)
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(function () {
                        return User::orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->searchable(),

                // 3. Record type se (User, Doctor, Patient ...)
                SelectFilter::make('subject_type')
                    ->label('Record type')
                    ->options(function () {
                        return ActivityLog::query()
                            ->distinct()
                            ->orderBy('subject_type')
                            ->pluck('subject_type', 'subject_type')
                            ->toArray();
                    }),

                // 4. Date se (kab se kab tak)
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From date'),
                        DatePicker::make('until')->label('Until date'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }

                        if (! empty($data['until'])) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No activity yet')
            ->paginated([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view'  => ViewActivityLog::route('/{record}'),
        ];
    }
}