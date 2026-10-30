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
       Schema::create('followup_forms', function (Blueprint $table) {
            $table->id();
            $table->string('form_name', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('version_number')->default(1);
            $table->json('questions');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followup_forms');
    }
};
