<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use App\Filament\Admin\Resources\Patients\Pages\ViewPatient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'alerts';

    protected static ?string $title = 'Alerts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewPatient::class;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alert_type')
            ->columns([
                TextColumn::make('triggered_at')->label('Triggered at')->dateTime('d M Y, h:i A'),
                TextColumn::make('alert_type')->label('Type')->badge(),
                TextColumn::make('severity')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'critical', 'high' => 'danger',
                            'medium'           => 'warning',
                            default            => 'gray',
                        };
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'open'         => 'danger',
                            'under_action' => 'warning',
                            'resolved'     => 'success',
                            default        => 'gray',
                        };
                    }),
                TextColumn::make('trigger_reason')->label('Reason')->limit(50),
                TextColumn::make('missed_count')->label('Missed days')->placeholder('-'),
            ])
            ->defaultSort('triggered_at', 'desc');
    }
}