<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('substance_use_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('substance_name', 100);
            $table->string('substance_category', 100)->nullable();
            $table->unsignedTinyInteger('age_at_first_use')->nullable();
            $table->string('duration_of_use', 100)->nullable();
            $table->string('frequency_of_use', 100)->nullable();
            $table->date('last_use_date')->nullable();
            $table->string('route_of_use', 50)->nullable();
            $table->text('previous_treatment_history')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('substance_use_histories');
    }
};
