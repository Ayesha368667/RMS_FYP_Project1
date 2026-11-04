<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlan extends Model
{
    use HasFactory;

    protected $table = 'treatment_plans';

    protected $fillable = [
        'patient_id', 'admission_id', 'created_by_doctor_id', 'plan_title',
        'goals', 'planned_interventions', 'start_date', 'target_end_date',
        'status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function createdByDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'created_by_doctor_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TreatmentPlanVersion::class);
    }

    public function therapySessions(): HasMany
    {
        return $this->hasMany(TherapySession::class);
    }
}
