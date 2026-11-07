<?php

namespace App\Filament\Admin\Resources\Patients\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient_number')->label('Patient ID')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Name')->searchable(),
                TextColumn::make('father_name')->label('Father name')->searchable(),
                TextColumn::make('user.phone')->label('Phone'),
                TextColumn::make('city'),
                TextColumn::make('status')->badge(),
                IconColumn::make('user.is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('id', 'desc');
    }
}