<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class Reserva extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'reservas';

    protected $fillable = [
        'numero_reserva',
        'usuario_id',
        'vuelo_ids', // Array de IDs de vuelos (ida y vuelta si aplica)
        'tipo_viaje', // ida, ida_vuelta
        'pasajeros', // Array de objetos pasajero
        'estado', // pendiente, confirmada, pagada, cancelada, vencida
        'fecha_creacion',
        'fecha_vencimiento',
        'precio_total',
        'preferencias', // JSON con preferencias (asientos, comidas, etc.)
        'observaciones'
    ];

    protected $casts = [
        'vuelo_ids' => 'array',
        'pasajeros' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'precio_total' => 'float',
        'preferencias' => 'array'
    ];

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con vuelos
     */
    public function vuelos()
    {
        return Vuelo::whereIn('_id', $this->vuelo_ids ?? []);
    }

    /**
     * Relación con billetes
     */
    public function billetes()
    {
        return $this->hasMany(Billete::class, 'reserva_id');
    }

    /**
     * Generar número de reserva único
     */
    public static function generarNumeroReserva()
    {
        do {
            $numero = 'R' . strtoupper(substr(uniqid(), -6));
        } while (self::where('numero_reserva', $numero)->exists());
        
        return $numero;
    }

    /**
     * Scope para reservas activas (no canceladas ni vencidas)
     */
    public function scopeActivas($query)
    {
        return $query->whereIn('estado', ['pendiente', 'confirmada', 'pagada']);
    }

    /**
     * Scope para reservas por usuario
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Verificar si la reserva ha vencido
     */
    public function haVencido()
    {
        return $this->fecha_vencimiento < Carbon::now() && $this->estado === 'pendiente';
    }

    /**
     * Marcar reserva como vencida
     */
    public function marcarComoVencida()
    {
        if ($this->haVencido()) {
            $this->update(['estado' => 'vencida']);
        }
    }
}