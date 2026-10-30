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
        Schema::create('family_contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('alert_id')->nullable()->constrained('alerts')->restrictOnDelete();
            $table->foreignId('family_contact_id')->constrained('family_contacts')->restrictOnDelete();
            $table->foreignId('contacted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('contact_type', 20);
            $table->string('channel', 20);
            $table->text('message')->nullable();
            $table->dateTime('contacted_at');
            $table->string('outcome', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_contact_logs');
    }
};
