<?php

namespace App\Filament\Admin\Resources\DoctorAssignments\Pages;

use App\Filament\Admin\Resources\DoctorAssignments\DoctorAssignmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDoctorAssignment extends CreateRecord
{
    protected static string $resource = DoctorAssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $model = static::getModel();
        $columns = Schema::getColumnListing((new $model)->getTable());

        // fill who assigned and when (only if these columns exist)
        if (in_array('assigned_by', $columns)) {
            $data['assigned_by'] = auth()->id();
        }

        if (in_array('assigned_at', $columns)) {
            $data['assigned_at'] = now();
        }

        return DB::transaction(function () use ($model, $data) {
            // a patient has only one active primary doctor:
            // close the old one (keep it as history) before adding the new one
            if (($data['assignment_role'] ?? null) === 'primary' && ($data['status'] ?? 'active') === 'active') {
                $model::query()
                    ->where('patient_id', $data['patient_id'])
                    ->where('assignment_role', 'primary')
                    ->where('status', 'active')
                    ->update([
                        'status' => 'completed',
                        'end_date' => now()->toDateString(),
                    ]);
            }

            return $model::create($data);
        });
    }
}