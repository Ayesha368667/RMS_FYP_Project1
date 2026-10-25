<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_assignment_id')->constrained('doctor_assignments')->restrictOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained('treatment_plans')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->restrictOnDelete();
            $table->dateTime('session_date');
            $table->string('session_type', 30); 
            $table->string('session_status', 20)->default('scheduled'); 
            $table->text('session_notes')->nullable(); 
            $table->text('progress_summary')->nullable();
            $table->text('next_steps')->nullable();
            $table->dateTime('next_session_date')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_sessions');
    }
};
