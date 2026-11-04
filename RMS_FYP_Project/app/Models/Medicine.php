<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $fillable = [
        'name', 'generic_name', 'category', 'form', 'description', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function patientMedicines() { return $this->hasMany(PatientMedicine::class); }
}    

