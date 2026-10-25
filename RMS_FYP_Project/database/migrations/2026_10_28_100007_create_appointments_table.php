<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('appointment_type', 30); 
            $table->text('reason')->nullable();
            $table->dateTime('requested_at');
            $table->dateTime('appointment_date')->nullable(); 
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('status', 20)->default('requested'); 
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

        
            $table->unique(['doctor_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
