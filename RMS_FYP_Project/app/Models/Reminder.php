<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = ['patient_id','followup_form_id','created_by','title','message','remind_at','channel','status'];
    protected $casts = ['remind_at' => 'datetime'];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function form()     { return $this->belongsTo(FollowupForm::class, 'followup_form_id'); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function alerts()   { return $this->hasMany(Alert::class); }
}
