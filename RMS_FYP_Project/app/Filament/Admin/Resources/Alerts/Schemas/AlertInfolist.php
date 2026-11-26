<?php

namespace App\Filament\Admin\Resources\Alerts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlertInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Alert details')
                ->columns(3)
                ->components([
                    TextEntry::make('patient.user.name')
                        ->label('Patient'),

                    TextEntry::make('alert_type')
                        ->label('Type')
                        ->badge()
                        ->formatStateUsing(function (string $state) {
                            return ucfirst(str_replace('_', ' ', $state));
                        }),

                    TextEntry::make('severity')
                        ->badge()
                        ->color(function (string $state) {
                            return match ($state) {
                                'critical', 'high' => 'danger',
                                'medium' => 'warning',
                                default => 'gray',
                            };
                        }),

                    TextEntry::make('status')
                        ->badge()
                        ->color(function (string $state) {
                            return match ($state) {
                                'open' => 'danger',
                                'under_action' => 'warning',
                                'resolved' => 'success',
                                default => 'gray',
                            };
                        }),

                    TextEntry::make('triggered_at')
                        ->label('Triggered At')
                        ->dateTime('d M Y, h:i A'),

                    TextEntry::make('missed_count')
                        ->label('Missed Days')
                        ->placeholder('-'),

                    TextEntry::make('raisedBy.name')
                        ->label('Raised By')
                        ->placeholder('System'),

                    TextEntry::make('trigger_reason')
                        ->label('Reason')
                        ->columnSpanFull(),
                ]),

            // Ye section sirf resolved alert par dikhega
            Section::make('Resolution')
                ->columns(3)
                ->visible(function ($record) {
                    return $record->status === 'resolved';
                })
                ->components([
                    TextEntry::make('resolvedBy.name')
                        ->label('Resolved By'),

                    TextEntry::make('resolved_at')
                        ->label('Resolved At')
                        ->dateTime('d M Y, h:i A'),

                    TextEntry::make('resolution_notes')
                        ->label('Notes')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}