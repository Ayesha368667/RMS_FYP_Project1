<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Alert;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class OpenAlerts extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Open alerts')
            ->query(function () {
                return Alert::query()
                    ->with('patient.user')
                    ->where('status', 'open')
                    ->orderBy('triggered_at', 'desc')
                    ->limit(5);
            })
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient'),

                TextColumn::make('severity')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'critical', 'high' => 'danger',
                            'medium' => 'warning',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('trigger_reason')
                    ->label('Reason')
                    ->limit(30),

                TextColumn::make('triggered_at')
                    ->label('Triggered')
                    ->since(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No open alerts');
    }
}