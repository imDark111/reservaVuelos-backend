<?php

namespace Database\Factories;

use App\Models\Aerolinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Aerolinea>
 */
class AerolineaFactory extends Factory
{
    protected $model = Aerolinea::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aerolineas = [
            [
                'codigo_iata' => 'XL',
                'codigo_icao' => 'LAN',
                'nombre' => 'LATAM Airlines Ecuador',
                'pais' => 'Ecuador',
                'contacto' => '+593-1800-LATAM',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/LATAM-Logo.png'
            ],
            [
                'codigo_iata' => 'EQ',
                'codigo_icao' => 'EQU',
                'nombre' => 'Equair',
                'pais' => 'Ecuador',
                'contacto' => '+593-4-228-0000',
                'logo_url' => 'https://equair.com.ec/logo.png'
            ],
            [
                'codigo_iata' => 'AA',
                'codigo_icao' => 'AAL',
                'nombre' => 'American Airlines',
                'pais' => 'Estados Unidos',
                'contacto' => '+1-800-433-7300',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/American-Airlines-Logo.png'
            ],
            [
                'codigo_iata' => 'CM',
                'codigo_icao' => 'CMP',
                'nombre' => 'Copa Airlines',
                'pais' => 'Panamá',
                'contacto' => '+507-217-2672',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/Copa-Airlines-Logo.png'
            ],
            [
                'codigo_iata' => 'AV',
                'codigo_icao' => 'AVA',
                'nombre' => 'Avianca',
                'pais' => 'Colombia',
                'contacto' => '+57-1-401-3434',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/Avianca-Logo.png'
            ],
            [
                'codigo_iata' => 'DL',
                'codigo_icao' => 'DAL',
                'nombre' => 'Delta Air Lines',
                'pais' => 'Estados Unidos',
                'contacto' => '+1-800-221-1212',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/Delta-Air-Lines-Logo.png'
            ],
            [
                'codigo_iata' => 'IB',
                'codigo_icao' => 'IBE',
                'nombre' => 'Iberia',
                'pais' => 'España',
                'contacto' => '+34-901-111-500',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/Iberia-Logo.png'
            ],
            [
                'codigo_iata' => 'KL',
                'codigo_icao' => 'KLM',
                'nombre' => 'KLM Royal Dutch Airlines',
                'pais' => 'Países Bajos',
                'contacto' => '+31-20-474-7747',
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/03/KLM-Logo.png'
            ]
        ];

        static $index = 0;
        $data = $aerolineas[$index % count($aerolineas)] ?? $aerolineas[0];
        $index++;

        return array_merge($data, [
            'activa' => true
        ]);
    }
}