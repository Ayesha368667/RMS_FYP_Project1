<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('followup_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('followup_form_id')->constrained('followup_forms')->restrictOnDelete();
            $table->date('due_date');
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedTinyInteger('mood_score')->nullable();
            $table->unsignedTinyInteger('pain_level')->nullable();
            $table->unsignedTinyInteger('craving_level')->nullable();
            $table->boolean('medicine_taken')->nullable();
            $table->boolean('relapse_reported')->nullable();
            $table->text('remarks')->nullable();
            $table->json('response_data')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->string('language', 5)->default('en');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('doctors')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['admission_id', 'due_date']);
            $table->index(['patient_id', 'due_date']);
            $table->index(['status', 'due_date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_responses');
    }
};
