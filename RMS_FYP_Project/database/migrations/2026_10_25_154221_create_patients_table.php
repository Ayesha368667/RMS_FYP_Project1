<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('patient_number', 30)->unique();
            $table->string('father_name', 150)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->date('registration_date');
            $table->string('status', 20)->default('registered')->index();
            $table->foreignId('created_by')->constrained('users')->restricOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

  
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
