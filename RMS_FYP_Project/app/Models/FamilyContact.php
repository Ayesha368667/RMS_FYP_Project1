<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyContact extends Model
{
    protected $fillable = [
        'patient_id', 'contact_name', 'relationship', 'phone', 'alternate_phone',
        'is_primary', 'is_emergency_contact', 'consent_status', 'consent_recorded_at',
        'preferred_language', 'is_active',
    ];

    protected $casts = [
        'is_primary'           => 'boolean',
        'is_emergency_contact' => 'boolean',
        'is_active'            => 'boolean',
        'consent_recorded_at'  => 'datetime',
    ];

    // Msg bhejny ki Ijazat badalte hi uska waqt khud likh do
    protected static function booted()
    {
        static::saving(function ($contact) {
            //isdirty mtlb kuch change to ni hua check kry ga
            if ($contact->isDirty('consent_status')) {
                //agr change hua he to time change kr do otherwise rehny do
                $contact->consent_recorded_at = now();
            }
        });
    }

    public function patient() { return $this->belongsTo(Patient::class); }
    public function logs()    { return $this->hasMany(FamilyContactLog::class); }
}