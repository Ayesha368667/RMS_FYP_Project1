<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertAction extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['alert_id','user_id','action_type','notes'];

    public function alert() { return $this->belongsTo(Alert::class); }
    public function user()  { return $this->belongsTo(User::class); }
}
