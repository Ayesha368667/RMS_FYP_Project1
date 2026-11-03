<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupForm extends Model
{
    protected $fillable = ['form_name', 'description', 'version_number', 'questions', 'is_active', 'created_by'];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function responses() { return $this->hasMany(FollowupResponse::class); }
}