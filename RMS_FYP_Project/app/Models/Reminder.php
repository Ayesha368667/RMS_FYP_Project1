<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'patient_id', 'reminder_type', 'remindable_type', 'remindable_id',
        'scheduled_at', 'sent_at', 'channel', 'status', 'failure_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function remindable() { return $this->morphTo(); }
}