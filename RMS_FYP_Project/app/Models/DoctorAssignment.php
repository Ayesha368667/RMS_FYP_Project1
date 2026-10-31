<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorAssignment extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'assigned_by', 'assignment_role',
        'start_date', 'end_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function patient() { return $this->belongsTo(Patient::class); }
    public function doctor() { return $this->belongsTo(Doctor::class); }
    public function assigner() { return $this->belongsTo(User::class, 'assigned_by'); }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
