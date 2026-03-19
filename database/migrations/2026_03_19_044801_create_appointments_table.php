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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->text('motivo_consulta')->nullable();
            $table->enum('estado', [
                'pendiente', 'confirmada', 'completada', 'cancelada'
            ])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['doctor_id', 'fecha', 'hora_inicio'], 'appointments_doctor_date_time_unique');
            
            $table->index(['fecha', 'estado']);
            $table->index(['patient_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
