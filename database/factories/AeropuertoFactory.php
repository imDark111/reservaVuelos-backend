<?php

namespace Database\Factories;

use App\Models\Aeropuerto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Aeropuerto>
 */
class AeropuertoFactory extends Factory
{
    protected $model = Aeropuerto::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $aeropuertos = [
            // Ecuador
            [
                'codigo_iata' => 'GYE',
                'codigo_icao' => 'SEGU',
                'nombre' => 'Aeropuerto Internacional José Joaquín de Olmedo',
                'ciudad' => 'Guayaquil',
                'pais' => 'Ecuador',
                'zona_horaria' => 'America/Guayaquil',
                'latitud' => -2.1574,
                'longitud' => -79.8837
            ],
            [
                'codigo_iata' => 'UIO',
                'codigo_icao' => 'SEQM',
                'nombre' => 'Aeropuerto Internacional Mariscal Sucre',
                'ciudad' => 'Quito',
                'pais' => 'Ecuador',
                'zona_horaria' => 'America/Guayaquil',
                'latitud' => -0.1292,
                'longitud' => -78.3576
            ],
            [
                'codigo_iata' => 'CUE',
                'codigo_icao' => 'SECU',
                'nombre' => 'Aeropuerto Mariscal Lamar',
                'ciudad' => 'Cuenca',
                'pais' => 'Ecuador',
                'zona_horaria' => 'America/Guayaquil',
                'latitud' => -2.8894,
                'longitud' => -78.9847
            ],
            // Destinos Internacionales
            [
                'codigo_iata' => 'MIA',
                'codigo_icao' => 'KMIA',
                'nombre' => 'Aeropuerto Internacional de Miami',
                'ciudad' => 'Miami',
                'pais' => 'Estados Unidos',
                'zona_horaria' => 'America/New_York',
                'latitud' => 25.7932,
                'longitud' => -80.2906
            ],
            [
                'codigo_iata' => 'PTY',
                'codigo_icao' => 'MPTO',
                'nombre' => 'Aeropuerto Internacional de Tocumen',
                'ciudad' => 'Ciudad de Panamá',
                'pais' => 'Panamá',
                'zona_horaria' => 'America/Panama',
                'latitud' => 9.0714,
                'longitud' => -79.3831
            ],
            [
                'codigo_iata' => 'BOG',
                'codigo_icao' => 'SKBO',
                'nombre' => 'Aeropuerto Internacional El Dorado',
                'ciudad' => 'Bogotá',
                'pais' => 'Colombia',
                'zona_horaria' => 'America/Bogota',
                'latitud' => 4.7016,
                'longitud' => -74.1469
            ],
            [
                'codigo_iata' => 'LIM',
                'codigo_icao' => 'SPJC',
                'nombre' => 'Aeropuerto Internacional Jorge Chávez',
                'ciudad' => 'Lima',
                'pais' => 'Perú',
                'zona_horaria' => 'America/Lima',
                'latitud' => -12.0219,
                'longitud' => -77.1143
            ],
            [
                'codigo_iata' => 'MAD',
                'codigo_icao' => 'LEMD',
                'nombre' => 'Aeropuerto Adolfo Suárez Madrid-Barajas',
                'ciudad' => 'Madrid',
                'pais' => 'España',
                'zona_horaria' => 'Europe/Madrid',
                'latitud' => 40.4719,
                'longitud' => -3.5626
            ],
            [
                'codigo_iata' => 'AMS',
                'codigo_icao' => 'EHAM',
                'nombre' => 'Aeropuerto de Ámsterdam-Schiphol',
                'ciudad' => 'Ámsterdam',
                'pais' => 'Países Bajos',
                'zona_horaria' => 'Europe/Amsterdam',
                'latitud' => 52.3086,
                'longitud' => 4.7639
            ],
            [
                'codigo_iata' => 'JFK',
                'codigo_icao' => 'KJFK',
                'nombre' => 'Aeropuerto Internacional John F. Kennedy',
                'ciudad' => 'Nueva York',
                'pais' => 'Estados Unidos',
                'zona_horaria' => 'America/New_York',
                'latitud' => 40.6413,
                'longitud' => -73.7781
            ],
            [
                'codigo_iata' => 'CCS',
                'codigo_icao' => 'SVMI',
                'nombre' => 'Aeropuerto Internacional Simón Bolívar',
                'ciudad' => 'Caracas',
                'pais' => 'Venezuela',
                'zona_horaria' => 'America/Caracas',
                'latitud' => 10.6013,
                'longitud' => -66.9908
            ],
            [
                'codigo_iata' => 'SCL',
                'codigo_icao' => 'SCEL',
                'nombre' => 'Aeropuerto Internacional Comodoro Arturo Merino Benítez',
                'ciudad' => 'Santiago',
                'pais' => 'Chile',
                'zona_horaria' => 'America/Santiago',
                'latitud' => -33.3928,
                'longitud' => -70.7856
            ],
            [
                'codigo_iata' => 'GRU',
                'codigo_icao' => 'SBGR',
                'nombre' => 'Aeropuerto Internacional de São Paulo-Guarulhos',
                'ciudad' => 'São Paulo',
                'pais' => 'Brasil',
                'zona_horaria' => 'America/Sao_Paulo',
                'latitud' => -23.4356,
                'longitud' => -46.4731
            ],
            [
                'codigo_iata' => 'EZE',
                'codigo_icao' => 'SAEZ',
                'nombre' => 'Aeropuerto Internacional Ezeiza Ministro Pistarini',
                'ciudad' => 'Buenos Aires',
                'pais' => 'Argentina',
                'zona_horaria' => 'America/Argentina/Buenos_Aires',
                'latitud' => -34.8222,
                'longitud' => -58.5358
            ],
            [
                'codigo_iata' => 'MEX',
                'codigo_icao' => 'MMMX',
                'nombre' => 'Aeropuerto Internacional Benito Juárez',
                'ciudad' => 'Ciudad de México',
                'pais' => 'México',
                'zona_horaria' => 'America/Mexico_City',
                'latitud' => 19.4363,
                'longitud' => -99.0721
            ]
        ];

        static $index = 0;
        $data = $aeropuertos[$index % count($aeropuertos)] ?? $aeropuertos[0];
        $index++;

        return array_merge($data, [
            'activo' => true
        ]);
    }
}