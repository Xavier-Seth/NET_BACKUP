<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'lrn',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'sex',
        'civil_status',
        'citizenship',
        'place_of_birth',
        'school_year',
        'guardian_phone',
        'address',
        'grade_level',
        'father_name',
        'mother_name',
        'guardian_name',
    ];

    // Append full_name attribute when using ->toArray() or in Vue
    protected $appends = ['full_name'];

    /**
     * Accessor for full_name
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /**
     * Relationship to grades table (linked by LRN)
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'lrn', 'lrn');
    }
}
