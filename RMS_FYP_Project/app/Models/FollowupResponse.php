<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupResponse extends Model
{
    protected $fillable = ['followup_form_id','patient_id','submitted_by','answers','submitted_at'];
    protected $casts = ['answers' => 'array', 'submitted_at' => 'datetime'];

    public function form()        { return $this->belongsTo(FollowupForm::class, 'followup_form_id'); }
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function submittedBy() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function alerts()      { return $this->hasMany(Alert::class); }
}
