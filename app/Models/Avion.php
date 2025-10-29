<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Avion extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $table = 'aviones';

    protected $fillable = [
        'codigo_avion',
        'aerolinea_id',
        'modelo',
        'num_asientos_total',
        'configuracion_asientos', // JSON con estructura de asientos por clase
        'activo'
    ];

    protected $casts = [
        'num_asientos_total' => 'integer',
        'configuracion_asientos' => 'array',
        'activo' => 'boolean'
    ];

    /**
     * Relación con aerolínea
     */
    public function aerolinea()
    {
        return $this->belongsTo(Aerolinea::class, 'aerolinea_id');
    }

    /**
     * Relación con vuelos
     */
    public function vuelos()
    {
        return $this->hasMany(Vuelo::class, 'avion_id');
    }
}