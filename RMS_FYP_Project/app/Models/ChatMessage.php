<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['conversation_id', 'sender', 'message'];

    public function conversation() { return $this->belongsTo(ChatConversation::class, 'conversation_id'); }
}