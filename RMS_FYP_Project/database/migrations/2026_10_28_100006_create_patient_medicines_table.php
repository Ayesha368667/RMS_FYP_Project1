<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('dosage', 100);
            $table->string('frequency', 100);
            $table->text('instructions')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medicines');
    }
};
