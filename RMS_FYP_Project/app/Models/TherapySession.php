<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapySession extends Model
{
    use HasFactory;

    protected $table = 'therapy_sessions';

    protected $fillable = [
        'patient_id', 'doctor_assignment_id', 'treatment_plan_id',
        'appointment_id', 'session_date', 'session_type', 'session_status',
        'session_notes', 'progress_summary', 'next_steps',
        'next_session_date', 'recorded_by',
    ];

    protected $casts = [
        'session_date' => 'datetime',
        'next_session_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctorAssignment(): BelongsTo
    {
        return $this->belongsTo(DoctorAssignment::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
