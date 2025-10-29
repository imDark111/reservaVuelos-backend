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
        // MongoDB Collection: aerolineas
        Schema::connection('mongodb')->create('aerolineas', function (Blueprint $collection) {
            $collection->index('codigo_iata'); // Índice único en código IATA
            $collection->index('codigo_icao'); // Índice único en código ICAO
            $collection->index('activa'); // Índice en estado activa
            $collection->index('pais'); // Índice en país
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('aerolineas');
    }
};
