<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanVersion extends Model
{
    use HasFactory;

    // this table has created_at only (history rows are never updated)
    public const UPDATED_AT = null;

    protected $table = 'treatment_plan_versions';

    protected $fillable = [
        'treatment_plan_id', 'version_number', 'plan_snapshot',
        'change_reason', 'changed_by', 'changed_at',
    ];

    protected $casts = [
        'plan_snapshot' => 'array',
        'changed_at' => 'datetime',
    ];

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
