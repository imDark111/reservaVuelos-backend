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
        // MongoDB Collection: aviones
        Schema::connection('mongodb')->create('aviones', function (Blueprint $collection) {
            $collection->index('codigo_avion'); // Índice único en código de avión
            $collection->index('aerolinea_id'); // Índice en aerolínea propietaria
            $collection->index('modelo'); // Índice en modelo del avión
            $collection->index('activo'); // Índice en estado activo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('aviones');
    }
};
