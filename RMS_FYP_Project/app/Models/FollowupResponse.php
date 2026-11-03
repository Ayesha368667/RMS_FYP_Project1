<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupResponse extends Model
{
    protected $fillable = [
        'patient_id', 'admission_id', 'followup_form_id', 'due_date', 'submitted_at',
        'mood_score', 'pain_level', 'craving_level', 'medicine_taken', 'relapse_reported',
        'remarks', 'response_data', 'is_critical', 'language', 'status',
        'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'due_date'         => 'date',
        'submitted_at'     => 'datetime',
        'reviewed_at'      => 'datetime',
        'response_data'    => 'array',
        'medicine_taken'   => 'boolean',
        'relapse_reported' => 'boolean',
        'is_critical'      => 'boolean',
    ];

    public function patient()   { return $this->belongsTo(Patient::class); }
    public function admission() { return $this->belongsTo(Admission::class); }
    public function form()      { return $this->belongsTo(FollowupForm::class, 'followup_form_id'); }
    public function reviewer()  { return $this->belongsTo(Doctor::class, 'reviewed_by'); }
}