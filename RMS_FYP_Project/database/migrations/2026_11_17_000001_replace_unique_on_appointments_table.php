<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old unique(doctor_id, appointment_date) also counted cancelled and
     * rejected rows, so a cancelled 10:00 slot could never be booked again.
     * AppointmentService now blocks overlapping approved appointments itself.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['doctor_id', 'appointment_date']);
            $table->index(['doctor_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'appointment_date']);
            $table->unique(['doctor_id', 'appointment_date']);
        });
    }
};