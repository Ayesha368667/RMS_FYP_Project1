<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = [
        'user_id', 'staff_number', 'qualification', 'specialization',
        'professional_type', 'license_number', 'joining_date',
        'availability_status', 'photo_path',
    ];

    protected function casts(): array
    {
        return ['joining_date' => 'date'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function doctorAssignments() { return $this->hasMany(DoctorAssignment::class); }
    public function activeAssignments() { return $this->hasMany(DoctorAssignment::class)->where('status', 'active'); }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_assignments')
            ->wherePivot('status', 'active')
            ->withPivot('assignment_role', 'start_date', 'end_date', 'status');
    }

    public function clinicalAssessments() { return $this->hasMany(ClinicalAssessment::class); }
    public function treatmentPlans() { return $this->hasMany(TreatmentPlan::class, 'created_by_doctor_id'); }
    public function patientMedicines() { return $this->hasMany(PatientMedicine::class); }
    public function appointments() { return $this->hasMany(Appointment::class); }
    public function therapySessions() { return $this->hasMany(TherapySession::class); }
    public function reviewedResponses() { return $this->hasMany(FollowupResponse::class, 'reviewed_by'); }
}    

