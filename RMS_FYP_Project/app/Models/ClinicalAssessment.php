<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalAssessment extends Model
{
    use HasFactory;

    protected $table = 'clinical_assessments';

    protected $fillable = [
        'patient_id', 'admission_id', 'doctor_id', 'assessment_type',
        'assessment_date', 'presenting_concerns', 'clinical_findings',
        'mental_health_notes', 'physical_health_notes', 'risk_assessment',
        'recommended_actions', 'next_assessment_date',
    ];

    protected $casts = [
        'assessment_date' => 'datetime',
        'next_assessment_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
