<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->string('assessment_type', 30); 
            $table->dateTime('assessment_date');
            $table->text('presenting_concerns');
            $table->text('clinical_findings');
            $table->text('mental_health_notes')->nullable();
            $table->text('physical_health_notes')->nullable();
            $table->text('risk_assessment')->nullable();
            $table->text('recommended_actions')->nullable();
            $table->date('next_assessment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_assessments');
    }
};
