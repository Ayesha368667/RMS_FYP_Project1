<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowupForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_name', 
        'description', 
        'version_number', 
        'questions', 
        'is_active', 
        'created_by'
    ];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];
}


  