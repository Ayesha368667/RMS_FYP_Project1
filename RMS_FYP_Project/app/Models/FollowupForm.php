<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupForm extends Model
{
    protected $fillable = ['title','description','questions','is_default','is_active','created_by'];
    protected $casts = ['questions' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];

    public function responses()  { return $this->hasMany(FollowupResponse::class); }
    public function reminders()  { return $this->hasMany(Reminder::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
}
