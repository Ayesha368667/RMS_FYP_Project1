<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'patient_number', 'father_name', 'date_of_birth', 'gender',
        'address', 'city', 'photo_path', 'registration_date', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'registration_date' => 'date'];
    }

        public function user() { return $this->belongsTo(User::class); }
        public function creator() { return $this->belongsTo(User::class, 'created_by'); }
        public function doctorAssignments() { return $this->hasMany(DoctorAssignment::class); }
        public function activeAssignments() { return $this->hasMany(DoctorAssignment::class)->where('status', 'active'); }
        public function admissions() { return $this->hasMany(Admission::class); }
        public function substanceUseHistories() { return $this->hasMany(SubstanceUseHistory::class); }
        public function clinicalAssessments() { return $this->hasMany(ClinicalAssessment::class); }
        public function treatmentPlans() { return $this->hasMany(TreatmentPlan::class); }
        public function patientMedicines() { return $this->hasMany(PatientMedicine::class); }
        public function appointments() { return $this->hasMany(Appointment::class); }
        public function therapySessions() { return $this->hasMany(TherapySession::class); }
        public function followupResponses() { return $this->hasMany(FollowupResponse::class); }
        public function reminders() { return $this->hasMany(Reminder::class); }
        public function alerts() { return $this->hasMany(Alert::class); }
        public function familyContacts() { return $this->hasMany(FamilyContact::class); }
        public function familyContactLogs() { return $this->hasMany(FamilyContactLog::class); }
        public function chatConversations() { return $this->hasMany(ChatConversation::class); }
    }    

