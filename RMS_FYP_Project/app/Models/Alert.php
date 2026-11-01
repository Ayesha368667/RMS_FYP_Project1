<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['patient_id','followup_response_id','reminder_id','type','severity','title','message','status'];

    public function patient()   { return $this->belongsTo(Patient::class); }
    public function response()  { return $this->belongsTo(FollowupResponse::class, 'followup_response_id'); }
    public function reminder()  { return $this->belongsTo(Reminder::class); }
    public function actions()   { return $this->hasMany(AlertAction::class); }
}
