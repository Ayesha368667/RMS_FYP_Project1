<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['chat_conversation_id','sender_id','message','read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function conversation() { return $this->belongsTo(ChatConversation::class, 'chat_conversation_id'); }
    public function sender()       { return $this->belongsTo(User::class, 'sender_id'); }

}
