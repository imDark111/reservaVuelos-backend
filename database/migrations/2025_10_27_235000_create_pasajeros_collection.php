<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MongoDB Collection: pasajeros
        Schema::connection('mongodb')->create('pasajeros', function (Blueprint $collection) {
            $collection->index('documento_identidad'); // Índice en documento de identidad
            $collection->index('reserva_id'); // Índice en reserva asociada
            $collection->index('tipo_documento'); // Índice en tipo de documento
            $collection->index('fecha_nacimiento'); // Índice en fecha de nacimiento
            $collection->index('email'); // Índice en email del pasajero
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('pasajeros');
    }
};
