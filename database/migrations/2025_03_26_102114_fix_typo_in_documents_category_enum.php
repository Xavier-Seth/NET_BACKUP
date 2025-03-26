<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix typo: ECCRPD → ECCRD
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRD') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back if needed
        DB::statement("ALTER TABLE documents MODIFY category ENUM('Form 137', 'PSA', 'ECCRPD') NOT NULL");
    }
};
