<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Update ENUM column to allow NULL
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRD') NULL");
    }

    public function down(): void
    {
        // Revert ENUM column to NOT NULL
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRD') NOT NULL");
    }
};
