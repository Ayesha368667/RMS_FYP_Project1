<?php

namespace App\Filament\Admin\Resources\Alerts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AlertActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    protected static ?string $title = 'Action log';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action_type')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A'),

                TextColumn::make('user.name')
                    ->label('By'),

                TextColumn::make('action_type')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('notes')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}