<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plan_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('plan_snapshot');
            $table->text('change_reason');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('changed_at');
            $table->timestamp('created_at')->nullable(); 

            $table->unique(['treatment_plan_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_versions');
    }
};
