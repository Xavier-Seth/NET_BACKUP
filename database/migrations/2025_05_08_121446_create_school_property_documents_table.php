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
        Schema::create('school_property_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('property_type'); // ICS, RIS
            $table->string('document_no')->nullable(); // ICS No / RIS No
            $table->date('issued_date')->nullable();
            $table->string('received_by')->nullable();
            $table->date('received_date')->nullable();
            $table->text('description')->nullable();
            $table->string('name');
            $table->string('path');
            $table->string('pdf_preview_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_property_documents');
    }
};
