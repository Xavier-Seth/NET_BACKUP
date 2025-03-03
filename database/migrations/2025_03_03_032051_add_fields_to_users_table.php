<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->after('id');
            }
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->after('last_name');
            }
            if (!Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'sex')) {
                $table->enum('sex', ['Male', 'Female'])->after('middle_name');
            }
            if (!Schema::hasColumn('users', 'civil_status')) {
                $table->enum('civil_status', ['Single', 'Married', 'Widowed'])->after('sex');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('civil_status'); // Allow NULL first
            }
            if (!Schema::hasColumn('users', 'religion')) {
                $table->string('religion')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->after('religion');
            }
        });

        // Ensure `date_of_birth` is not NULL for existing records
        \Illuminate\Support\Facades\DB::statement("UPDATE users SET date_of_birth = '2000-01-01' WHERE date_of_birth IS NULL");
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_name',
                'first_name',
                'middle_name',
                'sex',
                'civil_status',
                'date_of_birth',
                'religion',
                'phone_number'
            ]);
        });
    }
};
