<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure only one row exists
        if (!SystemSetting::first()) {
            SystemSetting::create([
                'school_name' => 'Rizal Central School',
                'logo_path' => null,
            ]);
        }
    }
}
