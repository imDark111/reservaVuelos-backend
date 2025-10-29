<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Pasajero extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'pasajeros';

    protected $fillable = [
        'reserva_id',
        'nombres',
        'apellidos',
        'tipo_documento', // cedula, pasaporte, etc.
        'numero_documento',
        'fecha_nacimiento',
        'nacionalidad',
        'email',
        'telefono',
        'tipo_pasajero', // adulto, menor, infante
        'necesidades_especiales', // array
        'asientos_asignados' // array con asientos por vuelo
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'necesidades_especiales' => 'array',
        'asientos_asignados' => 'array'
    ];

    /**
     * Relación con reserva
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con billetes
     */
    public function billetes()
    {
        return $this->hasMany(Billete::class, 'pasajero_id');
    }

    /**
     * Calcular edad del pasajero
     */
    public function getEdadAttribute()
    {
        return $this->fecha_nacimiento->diffInYears(now());
    }

    /**
     * Obtener nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    /**
     * Determinar tipo de pasajero por edad
     */
    public function determinarTipoPasajero()
    {
        $edad = $this->edad;
        
        if ($edad < 2) {
            return 'infante';
        } elseif ($edad < 12) {
            return 'menor';
        } else {
            return 'adulto';
        }
    }
}