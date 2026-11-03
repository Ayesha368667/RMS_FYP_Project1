<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $fillable = ['patient_id', 'started_at', 'ended_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function messages() { return $this->hasMany(ChatMessage::class, 'conversation_id'); }
}