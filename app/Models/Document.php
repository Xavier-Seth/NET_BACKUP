<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'path',
        'mime_type',
        'size',
        'type',
        'category',
        'lrn',
        'pdf_preview_path',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'lrn', 'lrn');
    }

    // ✅ Add this relationship to get uploader info
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}