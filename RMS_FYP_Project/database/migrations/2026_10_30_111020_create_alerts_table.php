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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->restrictOnDelete();
            $table->string('alert_type', 30);
            $table->foreignId('followup_response_id')->nullable()->constrained('followup_responses')->restrictOnDelete();
            $table->string('severity', 10)->default('high');
            $table->string('trigger_reason', 255);
            $table->unsignedTinyInteger('missed_count')->nullable();
            $table->dateTime('triggered_at');
            $table->string('status', 20)->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('raised_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('escalated_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'triggered_at']);
            $table->index(['patient_id', 'status']);
        });
     }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
