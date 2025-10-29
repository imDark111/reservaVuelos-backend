<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Vuelo extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'vuelos';

    protected $fillable = [
        'codigo_vuelo',
        'avion_id',
        'origen_id',
        'destino_id',
        'fecha_salida',
        'fecha_llegada',
        'duracion_minutos',
        'estado_vuelo', // programado, en_hora, retrasado, cancelado, abordando, en_vuelo, aterrizado
        'precio_base',
        'tarifas', // JSON con precios por clase (economica, premium, ejecutiva, primera)
        'asientos_disponibles', // JSON con disponibilidad por clase
        'es_directo',
        'activo'
    ];

    protected $casts = [
        'fecha_salida' => 'datetime',
        'fecha_llegada' => 'datetime',
        'duracion_minutos' => 'integer',
        'precio_base' => 'float',
        'tarifas' => 'array',
        'asientos_disponibles' => 'array',
        'es_directo' => 'boolean',
        'activo' => 'boolean'
    ];

    /**
     * Relación con avión
     */
    public function avion()
    {
        return $this->belongsTo(Avion::class, 'avion_id');
    }

    /**
     * Relación con aeropuerto origen
     */
    public function origen()
    {
        return $this->belongsTo(Aeropuerto::class, 'origen_id');
    }

    /**
     * Relación con aeropuerto destino
     */
    public function destino()
    {
        return $this->belongsTo(Aeropuerto::class, 'destino_id');
    }

    /**
     * Relación con reservas
     */
    public function reservas()
    {
        return $this->belongsToMany(Reserva::class, null, 'vuelo_ids', 'reserva_id');
    }

    /**
     * Relación con billetes
     */
    public function billetes()
    {
        return $this->hasMany(Billete::class, 'vuelo_id');
    }

    /**
     * Scope para vuelos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar vuelos por ruta
     */
    public function scopePorRuta($query, $origenId, $destinoId)
    {
        return $query->where('origen_id', $origenId)
                    ->where('destino_id', $destinoId);
    }

    /**
     * Scope para buscar vuelos por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_salida', $fecha);
    }

    /**
     * Accessor para duración formateada
     */
    public function getDuracionFormateadaAttribute()
    {
        $horas = floor($this->duracion_minutos / 60);
        $minutos = $this->duracion_minutos % 60;
        return sprintf('%dh %02dm', $horas, $minutos);
    }
}