<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdmissionService
{
    public function admit(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            // lock the patient row so two admins cannot admit the same patient at once
            $patient = Patient::query()->lockForUpdate()->findOrFail($data['patient_id']);

            if ($patient->status === 'inactive') {
                throw new InvalidArgumentException('An inactive patient cannot be admitted.');
            }

            $hasActiveAdmission = Admission::query()
                ->where('patient_id', $patient->id)
                ->where('status', 'admitted')
                ->exists();

            if ($hasActiveAdmission) {
                throw new InvalidArgumentException('This patient already has an active admission.');
            }

            $data['admission_number'] = $this->nextAdmissionNumber();
            $data['admitted_by'] = auth()->id();
            $data['status'] = 'admitted';
            $data['admission_date'] = $data['admission_date'] ?? now();

            $admission = Admission::create($data);

            // patient status update
            $patient->update(['status' => 'admitted']);

            return $admission;
        });
    }

    private function nextAdmissionNumber(): string
    {
        $prefix = 'ADM-'.now()->format('Y').'-';

        $last = Admission::withTrashed()
            ->where('admission_number', 'like', $prefix.'%')
            ->orderByDesc('admission_number')
            ->value('admission_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}