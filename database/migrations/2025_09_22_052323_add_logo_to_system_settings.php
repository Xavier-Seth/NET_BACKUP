<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->default('Rizal Central School');
            $table->string('logo_path')->nullable(); // store file path for uploaded logo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
