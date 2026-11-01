<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = ['patient_id','created_by','title'];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function messages() { return $this->hasMany(ChatMessage::class); }
}
