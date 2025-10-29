<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        User::create([
            'nombres' => 'Admin',
            'apellidos' => 'Sistema',
            'email' => 'admin@reservavuelos.com',
            'password' => Hash::make('password123'),
            'fecha_registro' => now(),
            'activo' => true
        ]);

        // Crear usuarios de prueba
        $usuariosPrueba = [
            [
                'nombres' => 'Juan Carlos',
                'apellidos' => 'Rodríguez Pérez',
                'email' => 'juan.rodriguez@email.com',
                'password' => Hash::make('password123'),
                'tarjeta_credito' => encrypt('4111111111111111'),
                'fecha_registro' => now()->subDays(30),
                'activo' => true
            ],
            [
                'nombres' => 'María Elena',
                'apellidos' => 'García López',
                'email' => 'maria.garcia@email.com',
                'password' => Hash::make('password123'),
                'tarjeta_credito' => encrypt('5555555555554444'),
                'fecha_registro' => now()->subDays(15),
                'activo' => true
            ],
            [
                'nombres' => 'Carlos Alberto',
                'apellidos' => 'Vásquez Torres',
                'email' => 'carlos.vasquez@email.com',
                'password' => Hash::make('password123'),
                'tarjeta_credito' => encrypt('4000000000000002'),
                'fecha_registro' => now()->subDays(7),
                'activo' => true
            ],
            [
                'nombres' => 'Ana Patricia',
                'apellidos' => 'Morales Cruz',
                'email' => 'ana.morales@email.com',
                'password' => Hash::make('password123'),
                'fecha_registro' => now()->subDays(3),
                'activo' => true
            ],
            [
                'nombres' => 'Luis Fernando',
                'apellidos' => 'Ramírez Silva',
                'email' => 'luis.ramirez@email.com',
                'password' => Hash::make('password123'),
                'tarjeta_credito' => encrypt('3782822463100005'),
                'fecha_registro' => now()->subDay(),
                'activo' => true
            ]
        ];

        foreach ($usuariosPrueba as $usuario) {
            User::create($usuario);
        }

        $this->command->info('👥 Usuarios creados: ' . User::count());
    }
}