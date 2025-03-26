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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('lrn')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('birthdate');
            $table->string('sex');
            $table->string('civil_status')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('school_year')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('address')->nullable();
            $table->string('grade_level');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('form137')->nullable();
            $table->string('psa')->nullable();
            $table->string('eccrd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
