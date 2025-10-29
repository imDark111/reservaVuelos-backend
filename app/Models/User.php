<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombres',
        'apellidos', 
        'email',
        'password',
        'tarjeta_credito',
        'fecha_registro',
        'activo'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'tarjeta_credito'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean'
        ];
    }

    /**
     * Relación con reservas
     */
    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'usuario_id');
    }

    /**
     * Relación con billetes
     */
    public function billetes()
    {
        return $this->hasMany(Billete::class, 'usuario_id');
    }
}
