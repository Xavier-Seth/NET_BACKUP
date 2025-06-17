<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'name_extension',
        'employee_id',
        'position',
        'birth_date',
        'department',
        'date_hired',
        'contact',
        'email',
        'address',
        'remarks',
        'pds_file_path',
        'photo_path',
        'status',        // ✅ newly fillable
    ];

    /**
     * Get all documents uploaded for this teacher.
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Scope only Active teachers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope only Inactive teachers.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }
}
