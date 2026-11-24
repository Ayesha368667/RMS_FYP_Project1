<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostDischargeNote extends Model
{
    // the table has only created_at: a note is never changed
    const UPDATED_AT = null;

    protected $table = 'post_discharge_notes';

    protected $fillable = [
        'patient_id', 'admission_id', 'doctor_id', 'note_type', 'note', 'requested_end_date',
    ];

    protected $casts = [
        'requested_end_date' => 'date',
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