<?php

namespace App\Filament\Admin\Resources\Doctors\RelationManagers;

use App\Models\DoctorAssignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssignedPatientsRelationManager extends RelationManager
{
    protected static string $relationship = 'doctorAssignments';

    protected static ?string $title = 'Assigned patients';

    // Sirf dekhna, koi add/edit/delete nahi
    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('assignment_role')
            ->modifyQueryUsing(function ($query) {
                return $query->with('patient.user');
            })
            ->columns([
                TextColumn::make('patient.patient_number')->label('Patient ID')->searchable(),
                TextColumn::make('patient.user.name')->label('Patient name'),
                TextColumn::make('assignment_role')->label('Role')->badge(),
                TextColumn::make('start_date')->label('Since')->date('d M Y'),
                TextColumn::make('end_date')->label('Until')->date('d M Y')->placeholder('-'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(function () {
                        return DoctorAssignment::query()->distinct()->pluck('status', 'status')->toArray();
                    })
                    ->default('active'),
            ])
            ->defaultSort('start_date', 'desc');
    }
}