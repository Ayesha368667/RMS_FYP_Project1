<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyContact extends Model
{
    protected $fillable = ['patient_id','name','relationship','phone','email','is_primary','receive_updates'];
    protected $casts = ['is_primary' => 'boolean', 'receive_updates' => 'boolean'];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function logs()    { return $this->hasMany(FamilyContactLog::class); }
}
