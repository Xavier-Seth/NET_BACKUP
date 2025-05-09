<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolPropertyDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'property_type',
        'document_no',
        'issued_date',
        'received_by',
        'received_date',
        'description',
        'name',
        'path',
        'pdf_preview_path',
        'mime_type',
        'size',
        'extracted_text',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
