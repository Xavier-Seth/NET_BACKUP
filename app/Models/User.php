<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'sex',
        'civil_status',
        'date_of_birth',
        'religion',
        'phone_number',
        'email',
        'password',
        'role',
        'status',
        'photo_path', // 
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d', //Ensures proper date format
    ];
}
