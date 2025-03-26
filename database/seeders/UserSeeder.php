<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'middle_name' => 'L.',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'date_of_birth' => '1995-07-15',
            'religion' => 'Catholic',
            'phone_number' => '09171234567',
            'email' => 'test@gmail.com',
            'role' => 'Admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => 'active',
        ]);
    }
}
