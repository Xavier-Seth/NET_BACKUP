<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('name_extension')->nullable(); // Jr., Sr., III
            $table->string('employee_id')->unique();
            $table->string('position')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('department')->nullable();
            $table->date('date_hired')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('remarks')->nullable();
            $table->string('pds_file_path')->nullable(); // File path to uploaded PDS
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
