<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('staff_number', 30)->unique();
            $table->string('qualification', 255);
            $table->string('specialization', 150);
            $table->string('professional_type', 30);
            $table->string('license_number', 100)->nullable()->unique();
            $table->date('joining_date');
            $table->string('availability_status', 30)->default('available');
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
