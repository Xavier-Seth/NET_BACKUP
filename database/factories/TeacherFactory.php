<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();

        return [
            'user_id' => User::factory(),
            'employee_id' => 'EMP-' . $this->faker->unique()->numberBetween(1000, 9999),

            'first_name' => $first,
            'last_name' => $last,
            'middle_name' => null,

            // ✅ REQUIRED BY DB
            'full_name' => "{$first} {$last}",

            'status' => 'Active',
        ];
    }
}
