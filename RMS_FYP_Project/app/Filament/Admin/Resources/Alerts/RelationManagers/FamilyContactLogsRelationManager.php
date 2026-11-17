<?php

namespace App\Filament\Admin\Resources\Alerts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyContactLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'familyContactLogs';

    protected static ?string $title = 'Family contact logs';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('channel')
            ->columns([
                TextColumn::make('contacted_at')
                    ->label('Contacted At')
                    ->dateTime('d M Y, h:i A'),

                TextColumn::make('familyContact.contact_name')
                    ->label('Family Member'),

                TextColumn::make('familyContact.relationship')
                    ->label('Relation'),

                TextColumn::make('contact_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return ucfirst(str_replace('_', ' ', $state));
                    }),

                TextColumn::make('channel')
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return strtoupper($state);
                    }),

                TextColumn::make('outcome')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'sent', 'delivered' => 'success',
                            'failed' => 'danger',
                            'pending' => 'warning',
                            default => 'gray',
                        };
                    }),

                // System ne SMS bheji ho to contacted_by khali hota hai
                TextColumn::make('contactedBy.name')
                    ->label('Contacted By')
                    ->placeholder('System'),

                TextColumn::make('message')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('failure_reason')
                    ->label('Failure Reason')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remarks')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('contacted_at', 'desc');
    }
}