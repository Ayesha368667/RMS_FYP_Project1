<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'admissions';

    protected $fillable = [
        'patient_id', 'batch_id', 'admission_number', 'admission_date',
        'admission_type', 'admission_reason', 'referral_source',
        'admission_photo_path', 'admitted_by', 'status', 'discharge_date',
        'discharge_type', 'discharged_by', 'followup_start_date',
        'followup_end_date',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'followup_start_date' => 'date',
        'followup_end_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    public function clinicalAssessments(): HasMany
    {
        return $this->hasMany(ClinicalAssessment::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function patientMedicines(): HasMany
    {
        return $this->hasMany(PatientMedicine::class);
    }
}
