<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use App\Filament\Admin\Resources\Patients\Pages\ViewPatient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FollowupResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'followupResponses';

    protected static ?string $title = 'Follow-ups';

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
            ->recordTitleAttribute('due_date')
            ->columns([
                TextColumn::make('due_date')->label('Due date')->date('d M Y'),
                TextColumn::make('status')->badge(),
                TextColumn::make('submitted_at')->label('Submitted at')->dateTime('d M Y, h:i A')->placeholder('-'),
                TextColumn::make('mood_score')->label('Mood')->placeholder('-'),
                TextColumn::make('pain_level')->label('Pain')->placeholder('-'),
                TextColumn::make('craving_level')->label('Craving')->placeholder('-'),
                IconColumn::make('medicine_taken')->label('Medicine')->boolean(),
                IconColumn::make('relapse_reported')->label('Relapse')->boolean(),
                IconColumn::make('is_critical')->label('Critical')->boolean(),
            ])
            ->defaultSort('due_date', 'desc');
    }
}