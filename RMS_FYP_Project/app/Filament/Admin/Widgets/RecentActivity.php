<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivity extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent activity')
            ->query(function () {
                // Sabse naye 8 records
                return ActivityLog::query()
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->limit(8);
            })
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->since(),

                // user_id khali ho to system ne kiya hai
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System'),

                TextColumn::make('action')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                // App\Models\Patient ki jagah sirf "Patient" dikhao
                TextColumn::make('subject_type')
                    ->label('Record')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        return class_basename($state);
                    }),

                TextColumn::make('description')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('-'),
            ])
            ->paginated(false)
            ->emptyStateHeading('No activity yet');
    }
}