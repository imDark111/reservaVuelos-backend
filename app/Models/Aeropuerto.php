<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Aeropuerto extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'aeropuertos';

    protected $fillable = [
        'codigo_iata',
        'codigo_icao',
        'nombre',
        'ciudad',
        'pais',
        'zona_horaria',
        'latitud',
        'longitud',
        'activo'
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'activo' => 'boolean'
    ];

    /**
     * Vuelos que salen desde este aeropuerto
     */
    public function vuelosOrigen()
    {
        return $this->hasMany(Vuelo::class, 'origen_id');
    }

    /**
     * Vuelos que llegan a este aeropuerto
     */
    public function vuelosDestino()
    {
        return $this->hasMany(Vuelo::class, 'destino_id');
    }
}