<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    // Channels
    public const CHANNEL_IN_APP = 'in_app';
    public const CHANNEL_SMS    = 'sms';

    // Statuses
    public const STATUS_PENDING = 'pending'; // waiting (in-app reminder showing, or SMS not sent yet)
    public const STATUS_SENT    = 'sent';    // the SMS was sent (or written to the log by the log driver)
    public const STATUS_FAILED  = 'failed';  // the SMS could not be sent (see failure_reason)
    public const STATUS_DONE    = 'done';    // the patient did what the reminder asked
    public const STATUS_EXPIRED = 'expired'; // the day ended and the patient did not do it

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_DONE,
        self::STATUS_EXPIRED,
    ];

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