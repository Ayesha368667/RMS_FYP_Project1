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
        Schema::create('family_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('contact_name', 150);
            $table->string('relationship', 50);
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->string('consent_status', 20)->default('not_granted');
            $table->dateTime('consent_recorded_at')->nullable();
            $table->string('preffered_language', 5)->default('ur');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_contacts');
    }
};
