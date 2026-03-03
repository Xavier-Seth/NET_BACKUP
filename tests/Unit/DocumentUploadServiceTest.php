<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->optional()->firstName(),

            'sex' => 'Male',
            'civil_status' => 'Single',
            'date_of_birth' => '1995-01-01',
            'religion' => 'Roman Catholic',
            'phone_number' => '09123456789',

            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),

            'email_verified_at' => now(),
        ];
    }
}
