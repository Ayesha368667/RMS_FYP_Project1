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
            // 1. Pehle normal index banayein taake foreign key ko sahara mil jaye
            $table->index(['doctor_id', 'appointment_date']);

            // 2. Ab purana unique index drop karein
            $table->dropUnique(['doctor_id', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // 1. Pehle unique index wapas banayein
            $table->unique(['doctor_id', 'appointment_date']);

            // 2. Phir normal index drop karein
            $table->dropIndex(['doctor_id', 'appointment_date']);
        });
    }
};