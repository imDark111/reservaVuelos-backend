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
        // MongoDB Collection: billetes
        Schema::connection('mongodb')->create('billetes', function (Blueprint $collection) {
            $collection->index('numero_billete'); // Índice único en número de billete
            $collection->index('reserva_id'); // Índice en reserva asociada
            $collection->index('pasajero_id'); // Índice en pasajero asociado
            $collection->index('estado_billete'); // Índice en estado del billete
            $collection->index('codigo_confirmacion'); // Índice en código de confirmación
            $collection->index('asiento'); // Índice en asiento asignado
            $collection->index('fecha_emision'); // Índice en fecha de emisión
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('billetes');
    }
};
