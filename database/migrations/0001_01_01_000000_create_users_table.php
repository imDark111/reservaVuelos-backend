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
        // MongoDB Collection: users - Sistema de usuarios
        Schema::connection('mongodb')->create('users', function (Blueprint $collection) {
            $collection->index('email'); // Índice único en email
            $collection->index('activo'); // Índice en estado activo
            $collection->index('fecha_registro'); // Índice en fecha registro
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->drop('users');
    }
};
