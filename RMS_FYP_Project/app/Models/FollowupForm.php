<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupForm extends Model
{
<<<<<<< Updated upstream
    protected $fillable = ['form_name', 'description', 'version_number', 'questions', 'is_active', 'created_by'];
=======
    protected $fillable = ['form_name','description','questions','is_active','created_by'];
    protected $casts = ['questions' => 'array', 'is_active' => 'boolean'];
>>>>>>> Stashed changes

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function responses() { return $this->hasMany(FollowupResponse::class); }
}