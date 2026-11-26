<?php

namespace App\Filament\Admin\Widgets;

use App\Models\FollowupResponse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingFollowups extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pending follow-ups')
            ->query(function () {
                // Jo abhi submit nahi hue, sabse purani due date pehle
                return FollowupResponse::query()
                    ->with('patient.user')
                    ->whereNull('submitted_at')
                    ->orderBy('due_date')
                    ->limit(5);
            })
            ->columns([
                TextColumn::make('patient.user.name')
                    ->label('Patient'),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y'),

                TextColumn::make('followup_state')
                    ->label('Status')
                    ->badge()
                    ->state(function (FollowupResponse $record) {
                        if ($record->due_date->lt(today())) {
                            return 'Missed';
                        }

                        return 'Pending';
                    })
                    ->color(function (string $state) {
                        return $state === 'Missed' ? 'danger' : 'warning';
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No pending follow-ups');
    }
}