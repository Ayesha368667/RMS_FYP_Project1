<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_discharge_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->string('note_type', 20)->default('note'); // note, extension_request
            $table->text('note');
            $table->date('requested_end_date')->nullable(); // only for extension_request
            $table->timestamp('created_at')->useCurrent();

            $table->index(['admission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_discharge_notes');
    }
};