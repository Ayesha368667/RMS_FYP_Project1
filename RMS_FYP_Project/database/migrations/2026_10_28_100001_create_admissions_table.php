<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            // batches table is not confirmed yet, so no foreign key for now
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('admission_number', 30)->unique();
            $table->dateTime('admission_date');
            $table->string('admission_type', 30); // new, readmission, transfer
            $table->text('admission_reason');
            $table->string('referral_source', 150)->nullable();
            $table->string('admission_photo_path', 255)->nullable();
            $table->foreignId('admitted_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 30)->default('admitted'); // admitted, discharged, cancelled
            $table->dateTime('discharge_date')->nullable();
            $table->string('discharge_type', 30)->nullable();
            $table->foreignId('discharged_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('followup_start_date')->nullable();
            $table->date('followup_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
