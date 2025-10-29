<?php

namespace Database\Factories;

use App\Models\Avion;
use App\Models\Aerolinea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Avion>
 */
class AvionFactory extends Factory
{
    protected $model = Avion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $configuracionesAviones = [
            // Airbus A320 - Configuración típica
            [
                'modelo' => 'Airbus A320',
                'num_asientos_total' => 180,
                'configuracion_asientos' => [
                    'ejecutiva' => [
                        'filas' => '1-6',
                        'asientos_por_fila' => 4,
                        'total' => 24
                    ],
                    'premium' => [
                        'filas' => '7-9',
                        'asientos_por_fila' => 6,
                        'total' => 18
                    ],
                    'economica' => [
                        'filas' => '10-30',
                        'asientos_por_fila' => 6,
                        'total' => 138
                    ]
                ]
            ],
            // Boeing 737-800
            [
                'modelo' => 'Boeing 737-800',
                'num_asientos_total' => 189,
                'configuracion_asientos' => [
                    'ejecutiva' => [
                        'filas' => '1-6',
                        'asientos_por_fila' => 4,
                        'total' => 24
                    ],
                    'premium' => [
                        'filas' => '7-10',
                        'asientos_por_fila' => 6,
                        'total' => 24
                    ],
                    'economica' => [
                        'filas' => '11-35',
                        'asientos_por_fila' => 6,
                        'total' => 141
                    ]
                ]
            ],
            // Boeing 767-300ER - Para vuelos internacionales largos
            [
                'modelo' => 'Boeing 767-300ER',
                'num_asientos_total' => 269,
                'configuracion_asientos' => [
                    'primera' => [
                        'filas' => '1-2',
                        'asientos_por_fila' => 4,
                        'total' => 8
                    ],
                    'ejecutiva' => [
                        'filas' => '6-10',
                        'asientos_por_fila' => 6,
                        'total' => 30
                    ],
                    'premium' => [
                        'filas' => '15-20',
                        'asientos_por_fila' => 7,
                        'total' => 42
                    ],
                    'economica' => [
                        'filas' => '21-45',
                        'asientos_por_fila' => 7,
                        'total' => 189
                    ]
                ]
            ],
            // Airbus A330-200 - Para vuelos intercontinentales
            [
                'modelo' => 'Airbus A330-200',
                'num_asientos_total' => 293,
                'configuracion_asientos' => [
                    'ejecutiva' => [
                        'filas' => '1-8',
                        'asientos_por_fila' => 4,
                        'total' => 32
                    ],
                    'premium' => [
                        'filas' => '10-15',
                        'asientos_por_fila' => 7,
                        'total' => 42
                    ],
                    'economica' => [
                        'filas' => '20-50',
                        'asientos_por_fila' => 8,
                        'total' => 219
                    ]
                ]
            ],
            // Boeing 787-8 Dreamliner
            [
                'modelo' => 'Boeing 787-8 Dreamliner',
                'num_asientos_total' => 242,
                'configuracion_asientos' => [
                    'ejecutiva' => [
                        'filas' => '1-6',
                        'asientos_por_fila' => 4,
                        'total' => 24
                    ],
                    'premium' => [
                        'filas' => '10-15',
                        'asientos_por_fila' => 6,
                        'total' => 36
                    ],
                    'economica' => [
                        'filas' => '20-45',
                        'asientos_por_fila' => 9,
                        'total' => 182
                    ]
                ]
            ],
            // Embraer E190 - Para rutas regionales
            [
                'modelo' => 'Embraer E190',
                'num_asientos_total' => 106,
                'configuracion_asientos' => [
                    'ejecutiva' => [
                        'filas' => '1-4',
                        'asientos_por_fila' => 4,
                        'total' => 16
                    ],
                    'economica' => [
                        'filas' => '5-27',
                        'asientos_por_fila' => 4,
                        'total' => 90
                    ]
                ]
            ]
        ];

        $configuracion = $this->faker->randomElement($configuracionesAviones);

        // Generar código de avión realista
        $prefijos = ['HC-', 'N', 'EC-', 'CP-', 'LV-', 'PT-'];
        $codigo = $this->faker->randomElement($prefijos) . strtoupper($this->faker->bothify('???'));

        return [
            'codigo_avion' => $codigo,
            'modelo' => $configuracion['modelo'],
            'num_asientos_total' => $configuracion['num_asientos_total'],
            'configuracion_asientos' => $configuracion['configuracion_asientos'],
            'activo' => true
        ];
    }

    /**
     * Indicate that the avion belongs to a specific aerolinea.
     */
    public function forAerolinea(Aerolinea $aerolinea): static
    {
        return $this->state(fn (array $attributes) => [
            'aerolinea_id' => $aerolinea->_id,
        ]);
    }
}