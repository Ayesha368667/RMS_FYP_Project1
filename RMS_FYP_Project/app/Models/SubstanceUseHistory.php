<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubstanceUseHistory extends Model
{
    use HasFactory;

    protected $table = 'substance_use_histories';

    protected $fillable = [
        'patient_id', 'recorded_by', 'substance_name', 'substance_category',
        'age_at_first_use', 'duration_of_use', 'frequency_of_use',
        'last_use_date', 'route_of_use', 'previous_treatment_history',
        'notes', 'recorded_at',
    ];

    protected $casts = [
        'last_use_date' => 'date',
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
