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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('reminder_type', 30);
            $table->string('remindable_type', 100)->nullable();
            $table->unsignedBigInteger('remindable_id')->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('sent_at')->nullable();
            $table->string('channel', 20)->default('in_app');
            $table->string('status', 20)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
            $table->index(['remindable_type', 'remindable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
