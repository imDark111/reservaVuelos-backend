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
        // MongoDB Collection: aeropuertos
        Schema::connection('mongodb')->create('aeropuertos', function (Blueprint $collection) {
            $collection->index('codigo_iata'); // Índice único en código IATA
            $collection->index('codigo_icao'); // Índice único en código ICAO
            $collection->index('ciudad'); // Índice en ciudad
            $collection->index('pais'); // Índice en país
            $collection->index('activo'); // Índice en estado activo
            $collection->index(['latitud', 'longitud']); // Índice geoespacial
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('aeropuertos');
    }
};
