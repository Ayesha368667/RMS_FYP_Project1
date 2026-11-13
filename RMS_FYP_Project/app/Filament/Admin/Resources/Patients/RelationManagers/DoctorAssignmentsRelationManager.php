<?php

namespace App\Filament\Admin\Resources\Patients\RelationManagers;

use App\Filament\Admin\Resources\Patients\Pages\ViewPatient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DoctorAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'doctorAssignments';

    protected static ?string $title = 'Assignments';

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
            ->recordTitleAttribute('assignment_role')
            ->columns([
                TextColumn::make('doctor.user.name')->label('Doctor'),
                TextColumn::make('assignment_role')->label('Role')->badge(),
                TextColumn::make('start_date')->label('Start')->date('d M Y'),
                TextColumn::make('end_date')->label('End')->date('d M Y')->placeholder('-'),
                TextColumn::make('status')->badge(),
                TextColumn::make('assigner.name')->label('Assigned by'),
                TextColumn::make('notes')->limit(40)->placeholder('-'),
            ])
            ->defaultSort('start_date', 'desc');
    }
}