<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class FixOldTeachersSeeder extends Seeder
{
    public function run()
    {
        // 1. Find teachers who are missing a user_id
        $orphanedTeachers = Teacher::whereNull('user_id')->get();

        if ($orphanedTeachers->isEmpty()) {
            $this->command->info("No orphaned teachers found. Everything looks good!");
            return;
        }

        $this->command->info("Found " . $orphanedTeachers->count() . " teachers without login accounts. Fixing...");

        foreach ($orphanedTeachers as $teacher) {

            DB::transaction(function () use ($teacher) {
                // Check if a user with this email already exists to prevent crashes
                $existingUser = User::where('email', $teacher->email)->first();

                if ($existingUser) {
                    // If user exists, just link them
                    $teacher->user_id = $existingUser->id;
                    $teacher->save();
                    $this->command->info("Linked existing user for: " . $teacher->full_name);
                } else {
                    // Create a NEW user account
                    $user = User::create([
                        'first_name' => $teacher->first_name,
                        'last_name' => $teacher->last_name,
                        'middle_name' => $teacher->middle_name,
                        'email' => $teacher->email,
                        'password' => Hash::make('password123'), // Default password
                        'role' => 'Teacher',
                        'status' => 'active', // Lowercase 'active'
                        'phone_number' => $teacher->contact ?? '',
                    ]);

                    // Link it
                    $teacher->user_id = $user->id;
                    $teacher->save();
                    $this->command->info("Created new account for: " . $teacher->full_name);
                }
            });
        }

        $this->command->info("All teachers have been fixed successfully!");
    }
}