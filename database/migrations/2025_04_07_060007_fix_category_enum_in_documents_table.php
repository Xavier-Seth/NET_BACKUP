<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        // Correct the ENUM values
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRD') NULL;");
    }

    public function down(): void
    {
        // Revert back to previous state (if needed)
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRPD') NULL;");
    }
};
