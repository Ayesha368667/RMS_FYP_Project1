<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'patient_id', 'admission_id', 'alert_type', 'followup_response_id', 'severity',
        'trigger_reason', 'missed_count', 'triggered_at', 'status', 'assigned_to',
        'raised_by', 'escalated_at', 'resolved_at', 'resolved_by', 'resolution_notes',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at'  => 'datetime',
    ];

    public function patient()           { return $this->belongsTo(Patient::class); }
    public function admission()         { return $this->belongsTo(Admission::class); }
    public function response()          { return $this->belongsTo(FollowupResponse::class, 'followup_response_id'); }
    public function assignee()          { return $this->belongsTo(User::class, 'assigned_to'); }
    public function raisedBy()          { return $this->belongsTo(User::class, 'raised_by'); }
    public function resolvedBy()        { return $this->belongsTo(User::class, 'resolved_by'); }
    public function actions()           { return $this->hasMany(AlertAction::class); }
    public function familyContactLogs() { return $this->hasMany(FamilyContactLog::class); }
}