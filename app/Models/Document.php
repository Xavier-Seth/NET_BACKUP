<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teacher_id',
        'category_id',
        'name',
        'path',
        'mime_type',
        'size',
        'pdf_preview_path',
        'extracted_text',
    ];

    /**
     * Get the teacher who owns this document.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the document category (e.g., PDS, NBI, PRC).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
