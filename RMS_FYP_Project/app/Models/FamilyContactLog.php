<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyContactLog extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['family_contact_id','user_id','contact_type','summary','contacted_at'];
    protected $casts = ['contacted_at' => 'datetime'];

    public function familyContact() { return $this->belongsTo(FamilyContact::class); }
    public function user()          { return $this->belongsTo(User::class); }
}
