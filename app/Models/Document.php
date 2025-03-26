<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'category',
        'lrn',
        'pdf_preview_path',
    ];

    // Optional: define relationship to student
    public function student()
    {
        return $this->belongsTo(Student::class, 'lrn', 'lrn');
    }
}
