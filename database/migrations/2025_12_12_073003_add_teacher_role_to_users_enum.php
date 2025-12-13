<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add "Teacher" to the enum
            $table->enum('role', ['Admin Staff', 'Admin', 'Teacher'])
                ->default('Admin Staff')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to original
            $table->enum('role', ['Admin Staff', 'Admin'])
                ->default('Admin Staff')
                ->change();
        });
    }
};
