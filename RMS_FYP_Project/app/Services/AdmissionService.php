<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Support\Carbon;
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

    public function discharge(Admission $admission, array $data): Admission
    {
        return DB::transaction(function () use ($admission, $data) {
            // reload with a lock so the same admission cannot be discharged twice
            $admission = Admission::query()->lockForUpdate()->findOrFail($admission->getKey());

            if ($admission->status !== 'admitted') {
                throw new InvalidArgumentException('Only an active admission can be discharged.');
            }

            $dischargeDate = Carbon::parse($data['discharge_date']);

            if ($dischargeDate->lt($admission->admission_date)) {
                throw new InvalidArgumentException('The discharge date cannot be before the admission date.');
            }

            $followupStart = Carbon::parse($data['followup_start_date']);
            $followupEnd = Carbon::parse($data['followup_end_date']);

            if ($followupStart->lt($dischargeDate->copy()->startOfDay())) {
                throw new InvalidArgumentException('The follow-up cannot start before the discharge date.');
            }

            if ($followupEnd->lt($followupStart)) {
                throw new InvalidArgumentException('The follow-up end date cannot be before the start date.');
            }

            $admission->update([
                'status' => 'discharged',
                'discharge_date' => $dischargeDate,
                'discharge_type' => $data['discharge_type'],
                'discharged_by' => auth()->id(),
                'followup_start_date' => $followupStart->toDateString(),
                'followup_end_date' => $followupEnd->toDateString(),
                'followup_frequency' => $data['followup_frequency'],
            ]);

            // patient status update
            $admission->patient()->update(['status' => 'discharged']);

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