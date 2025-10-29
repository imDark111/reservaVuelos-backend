<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Aerolinea extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'aerolineas';

    protected $fillable = [
        'codigo_iata',
        'codigo_icao', 
        'nombre',
        'pais',
        'contacto',
        'logo_url',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean'
    ];

    /**
     * Relación con aviones
     */
    public function aviones()
    {
        return $this->hasMany(Avion::class, 'aerolinea_id');
    }

    /**
     * Relación con vuelos a través de aviones
     */
    public function vuelos()
    {
        return $this->hasManyThrough(Vuelo::class, Avion::class, 'aerolinea_id', 'avion_id');
    }
}