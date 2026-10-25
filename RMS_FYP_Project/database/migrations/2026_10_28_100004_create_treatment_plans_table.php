<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->restrictOnDelete();
            $table->foreignId('created_by_doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->string('plan_title', 200);
            $table->text('goals');
            $table->text('planned_interventions');
            $table->date('start_date');
            $table->date('target_end_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, active, completed, discontinued
            $table->foreignId('approved_by')->nullable()->constrained('doctors')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plans');
    }
};
