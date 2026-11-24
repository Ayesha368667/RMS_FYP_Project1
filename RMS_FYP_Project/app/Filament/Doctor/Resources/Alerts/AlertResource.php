<?php

namespace App\Filament\Doctor\Resources\Alerts;

use App\Filament\Doctor\Resources\Alerts\Pages\ListAlerts;
use App\Filament\Doctor\Resources\Alerts\Pages\ViewAlert;
use App\Filament\Doctor\Resources\Alerts\RelationManagers\ActionNotesRelationManager;
use App\Filament\Doctor\Resources\Alerts\Tables\AlertsTable;
use App\Models\Alert;
use App\Support\DoctorDashboard;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;

    protected static ?string $navigationLabel = 'Alerts';

    protected static ?string $modelLabel = 'alert';

    protected static ?string $pluralModelLabel = 'Alerts';

    protected static string|UnitEnum|null $navigationGroup = 'Patient care';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function table(Table $table): Table
    {
        return AlertsTable::configure($table);
    }

    // alert detail page
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Alert')
                ->columns(3)
                ->components([
                    TextEntry::make('patient.patient_number')->label('Patient ID'),
                    TextEntry::make('patient.user.name')->label('Patient'),
                    TextEntry::make('triggered_at')->label('Triggered at')->dateTime('d M Y, h:i A'),

                    TextEntry::make('alert_type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                    TextEntry::make('severity')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'critical', 'high' => 'danger',
                            'medium' => 'warning',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),

                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'open' => 'danger',
                            'under_action' => 'warning',
                            'resolved' => 'success',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (?string $state): string => ucfirst(str_replace('_', ' ', (string) $state))),

                    TextEntry::make('trigger_reason')->label('Reason')->columnSpanFull(),
                    TextEntry::make('missed_count')->label('Missed days')->placeholder('-'),
                    TextEntry::make('escalated_at')->label('Escalated at')->dateTime('d M Y, h:i A')->placeholder('Not escalated'),
                    TextEntry::make('assignee.name')->label('Handled by')->placeholder('Nobody yet'),
                    TextEntry::make('response.due_date')->label('Related daily log date')->date('d M Y')->placeholder('-'),
                ]),

            Section::make('Resolution')
                ->columns(3)
                ->visible(fn (Alert $record): bool => $record->status === 'resolved')
                ->components([
                    TextEntry::make('resolvedBy.name')->label('Resolved by')->placeholder('-'),
                    TextEntry::make('resolved_at')->label('Resolved at')->dateTime('d M Y, h:i A')->placeholder('-'),
                    TextEntry::make('resolution_notes')->label('Notes')->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ActionNotesRelationManager::class,
        ];
    }

    // a doctor sees only alerts of HIS OWN active patients
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('patient_id', DoctorDashboard::myPatients()->select('patients.id'));
    }

    // red number in the menu = alerts still open
    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'open')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

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
            'index' => ListAlerts::route('/'),
            'view' => ViewAlert::route('/{record}'),
        ];
    }
}