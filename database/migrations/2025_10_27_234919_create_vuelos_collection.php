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
        // MongoDB Collection: vuelos
        Schema::connection('mongodb')->create('vuelos', function (Blueprint $collection) {
            $collection->index('codigo_vuelo'); // Índice único en código de vuelo
            $collection->index('avion_id'); // Índice en avión asignado
            $collection->index('origen_id'); // Índice en aeropuerto origen
            $collection->index('destino_id'); // Índice en aeropuerto destino
            $collection->index('fecha_salida'); // Índice en fecha de salida
            $collection->index('fecha_llegada'); // Índice en fecha de llegada
            $collection->index('estado_vuelo'); // Índice en estado del vuelo
            $collection->index('activo'); // Índice en estado activo
            $collection->index(['origen_id', 'destino_id', 'fecha_salida']); // Índice compuesto para búsquedas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('vuelos');
    }
};
