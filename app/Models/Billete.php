<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Billete extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'billetes';

    protected $fillable = [
        'codigo_billete',
        'reserva_id',
        'pasajero_id',
        'vuelo_id',
        'usuario_id',
        'asiento',
        'clase_servicio', // economica, premium, ejecutiva, primera
        'precio_pagado',
        'estado_billete', // emitido, usado, cancelado, reembolsado
        'fecha_emision',
        'fecha_vencimiento',
        'metodo_entrega', // email, aeropuerto, domicilio
        'direccion_entrega',
        'check_in_realizado',
        'fecha_check_in',
        'equipaje_facturado' // JSON con información del equipaje
    ];

    protected $casts = [
        'precio_pagado' => 'float',
        'fecha_emision' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'check_in_realizado' => 'boolean',
        'fecha_check_in' => 'datetime',
        'equipaje_facturado' => 'array'
    ];

    /**
     * Relación con reserva
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con pasajero
     */
    public function pasajero()
    {
        return $this->belongsTo(Pasajero::class, 'pasajero_id');
    }

    /**
     * Relación con vuelo
     */
    public function vuelo()
    {
        return $this->belongsTo(Vuelo::class, 'vuelo_id');
    }

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Generar código de billete único
     */
    public static function generarCodigoBillete()
    {
        do {
            $codigo = 'T' . strtoupper(substr(uniqid(), -8));
        } while (self::where('codigo_billete', $codigo)->exists());
        
        return $codigo;
    }

    /**
     * Scope para billetes válidos
     */
    public function scopeValidos($query)
    {
        return $query->whereIn('estado_billete', ['emitido', 'usado']);
    }

    /**
     * Scope por vuelo
     */
    public function scopePorVuelo($query, $vueloId)
    {
        return $query->where('vuelo_id', $vueloId);
    }

    /**
     * Realizar check-in
     */
    public function realizarCheckIn()
    {
        if ($this->estado_billete === 'emitido') {
            $this->update([
                'check_in_realizado' => true,
                'fecha_check_in' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Verificar si el billete puede ser usado
     */
    public function puedeSerUsado()
    {
        return $this->estado_billete === 'emitido' && 
               $this->fecha_vencimiento >= now();
    }
}