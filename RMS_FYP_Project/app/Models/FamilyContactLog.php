<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyContactLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id', 'alert_id', 'family_contact_id', 'contacted_by', 'contact_type',
        'channel', 'message', 'contacted_at', 'outcome', 'failure_reason', 'remarks',
    ];

    protected $casts = ['contacted_at' => 'datetime'];

    public function patient()       { return $this->belongsTo(Patient::class); }
    public function alert()         { return $this->belongsTo(Alert::class); }
    public function familyContact() { return $this->belongsTo(FamilyContact::class); }
    public function contactedBy()   { return $this->belongsTo(User::class, 'contacted_by'); }
}