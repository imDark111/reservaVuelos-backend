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
        // MongoDB Collection: reservas
        Schema::connection('mongodb')->create('reservas', function (Blueprint $collection) {
            $collection->index('codigo_reserva'); // Índice único en código de reserva
            $collection->index('usuario_id'); // Índice en usuario que reserva
            $collection->index('vuelo_id'); // Índice en vuelo reservado
            $collection->index('estado_reserva'); // Índice en estado de la reserva
            $collection->index('fecha_reserva'); // Índice en fecha de reserva
            $collection->index('clase_servicio'); // Índice en clase de servicio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('reservas');
    }
};
