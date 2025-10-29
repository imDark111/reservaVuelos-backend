<?php

namespace Database\Factories;

use App\Models\Vuelo;
use App\Models\Aeropuerto;
use App\Models\Avion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vuelo>
 */
class VueloFactory extends Factory
{
    protected $model = Vuelo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generar fechas desde 30 días atrás hasta diciembre 2025
        $fechaInicio = Carbon::now()->subDays(30);
        $fechaFin = Carbon::create(2025, 12, 31);
        $diasDisponibles = $fechaInicio->diffInDays($fechaFin);
        
        // Generar fecha de salida aleatoria en el rango
        $diasAleatorios = $this->faker->numberBetween(0, $diasDisponibles);
        $fechaSalida = $fechaInicio->copy()->addDays($diasAleatorios)
                            ->setTime($this->faker->numberBetween(5, 23), $this->faker->randomElement([0, 15, 30, 45]));

        // Duración típica de vuelos desde Ecuador
        $duracionesVuelo = [
            'domestico' => $this->faker->numberBetween(45, 120),      // 45min - 2h
            'regional' => $this->faker->numberBetween(120, 360),      // 2h - 6h
            'continental' => $this->faker->numberBetween(360, 720),   // 6h - 12h
            'intercontinental' => $this->faker->numberBetween(720, 900) // 12h - 15h
        ];

        $duracion = $this->faker->randomElement($duracionesVuelo);
        $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);

        // Determinar estado del vuelo basado en la fecha
        $estado = $this->determinarEstadoVuelo($fechaSalida, $fechaLlegada);

        // Precios base según distancia
        $preciosBase = [
            'domestico' => $this->faker->randomFloat(2, 80, 200),
            'regional' => $this->faker->randomFloat(2, 200, 500),
            'continental' => $this->faker->randomFloat(2, 500, 1200),
            'intercontinental' => $this->faker->randomFloat(2, 1200, 2500)
        ];

        $precioBase = $this->faker->randomElement($preciosBase);

        // Configurar tarifas por clase
        $tarifas = [
            'economica' => $precioBase,
            'premium' => $precioBase * 1.8,
            'ejecutiva' => $precioBase * 3.2,
            'primera' => $precioBase * 5.5
        ];

        return [
            'fecha_salida' => $fechaSalida,
            'fecha_llegada' => $fechaLlegada,
            'duracion_minutos' => $duracion,
            'estado_vuelo' => $estado,
            'precio_base' => $precioBase,
            'tarifas' => $tarifas,
            'es_directo' => $this->faker->boolean(85), // 85% de vuelos directos
            'activo' => true
        ];
    }

    /**
     * Determinar el estado del vuelo basado en las fechas
     */
    private function determinarEstadoVuelo(Carbon $fechaSalida, Carbon $fechaLlegada): string
    {
        $ahora = Carbon::now();
        
        // Si el vuelo ya terminó (fecha de llegada pasó)
        if ($fechaLlegada->isPast()) {
            // 90% aterrizado, 10% cancelado para vuelos pasados
            return $this->faker->randomElement([
                'aterrizado', 'aterrizado', 'aterrizado', 'aterrizado', 'aterrizado',
                'aterrizado', 'aterrizado', 'aterrizado', 'aterrizado', 'cancelado'
            ]);
        }
        
        // Si el vuelo está en curso (entre fecha de salida y llegada)
        if ($fechaSalida->isPast() && $fechaLlegada->isFuture()) {
            return $this->faker->randomElement(['en_vuelo', 'en_vuelo', 'abordando']);
        }
        
        // Si el vuelo es hoy pero aún no ha salido
        if ($fechaSalida->isToday() && $fechaSalida->isFuture()) {
            $horasHastaSalida = $ahora->diffInHours($fechaSalida);
            
            if ($horasHastaSalida <= 2) {
                // Vuelos próximos a salir
                return $this->faker->randomElement(['abordando', 'en_hora', 'retrasado']);
            } else {
                // Vuelos del día pero no tan próximos
                return $this->faker->randomElement(['programado', 'en_hora', 'retrasado']);
            }
        }
        
        // Si el vuelo es mañana
        if ($fechaSalida->isTomorrow()) {
            return $this->faker->randomElement(['programado', 'en_hora', 'retrasado', 'cancelado']);
        }
        
        // Vuelos futuros (más de 1 día)
        if ($fechaSalida->isFuture()) {
            // Mayoría programados, algunos pueden estar cancelados
            return $this->faker->randomElement([
                'programado', 'programado', 'programado', 'programado', 'programado',
                'programado', 'programado', 'en_hora', 'cancelado'
            ]);
        }
        
        // Fallback
        return 'programado';
    }

    /**
     * Vuelos desde Ecuador hacia destinos internacionales
     */
    public function desdeEcuador(): static
    {
        return $this->state(function (array $attributes) {
            // Aeropuertos de Ecuador como origen (incluyendo Cuenca)
            $aeropuertosEcuador = ['UIO', 'GYE', 'CUE'];
            $codigoOrigen = $this->faker->randomElement($aeropuertosEcuador);
            
            // Destinos internacionales según el aeropuerto de origen
            $destinosSegunOrigen = $this->obtenerDestinosInternacionales($codigoOrigen);
            $codigoDestino = $this->faker->randomElement($destinosSegunOrigen);
            
            // Ajustar características del vuelo según origen y destino
            $caracteristicas = $this->obtenerCaracteristicasInternacional($codigoOrigen, $codigoDestino);
            
            return [
                'codigo_origen' => $codigoOrigen,
                'codigo_destino' => $codigoDestino,
                'duracion_minutos' => $caracteristicas['duracion'],
                'precio_base' => $caracteristicas['precio'],
                'es_directo' => $caracteristicas['es_directo']
            ];
        });
    }

    /**
     * Obtener destinos internacionales disponibles según el aeropuerto de origen
     */
    private function obtenerDestinosInternacionales(string $codigoOrigen): array
    {
        $destinos = [
            'UIO' => [ // Quito - Mayor conectividad internacional
                'MIA', 'PTY', 'BOG', 'LIM', 'MAD', 'AMS', 'JFK', 'CCS', 
                'SCL', 'GRU', 'EZE', 'MEX', 'CUN', 'IAH', 'FLL'
            ],
            'GYE' => [ // Guayaquil - Conectividad regional y algunas internacionales
                'MIA', 'PTY', 'BOG', 'LIM', 'MAD', 'JFK', 'CCS', 
                'SCL', 'GRU', 'MEX', 'FLL', 'IAH'
            ],
            'CUE' => [ // Cuenca - Principalmente conexiones regionales
                'PTY', 'BOG', 'LIM', 'MIA', 'CCS', 'SCL', 'GRU'
            ]
        ];
        
        return $destinos[$codigoOrigen] ?? $destinos['UIO'];
    }

    /**
     * Obtener características del vuelo internacional según origen y destino
     */
    private function obtenerCaracteristicasInternacional(string $origen, string $destino): array
    {
        // Clasificar destinos por región/distancia
        $destinosRegionales = ['PTY', 'BOG', 'LIM', 'CCS'];
        $destinosContinentales = ['MIA', 'JFK', 'MEX', 'SCL', 'GRU', 'EZE', 'FLL', 'IAH', 'CUN'];
        $destinosIntercontinentales = ['MAD', 'AMS'];
        
        // Determinar tipo de vuelo
        if (in_array($destino, $destinosRegionales)) {
            $tipo = 'regional';
        } elseif (in_array($destino, $destinosContinentales)) {
            $tipo = 'continental';
        } else {
            $tipo = 'intercontinental';
        }
        
        // Ajustes específicos para Cuenca (aeropuerto más pequeño)
        $esDesdeCuenca = ($origen === 'CUE');
        
        $caracteristicas = [
            'regional' => [
                'duracion_min' => $esDesdeCuenca ? 150 : 120,
                'duracion_max' => $esDesdeCuenca ? 400 : 360,
                'precio_min' => $esDesdeCuenca ? 250 : 200,
                'precio_max' => $esDesdeCuenca ? 600 : 500,
                'es_directo' => $esDesdeCuenca ? $this->faker->boolean(60) : $this->faker->boolean(85)
            ],
            'continental' => [
                'duracion_min' => $esDesdeCuenca ? 400 : 360,
                'duracion_max' => $esDesdeCuenca ? 800 : 720,
                'precio_min' => $esDesdeCuenca ? 600 : 500,
                'precio_max' => $esDesdeCuenca ? 1400 : 1200,
                'es_directo' => $esDesdeCuenca ? $this->faker->boolean(40) : $this->faker->boolean(70)
            ],
            'intercontinental' => [
                'duracion_min' => $esDesdeCuenca ? 800 : 720,
                'duracion_max' => $esDesdeCuenca ? 1000 : 900,
                'precio_min' => $esDesdeCuenca ? 1400 : 1200,
                'precio_max' => $esDesdeCuenca ? 2800 : 2500,
                'es_directo' => $esDesdeCuenca ? $this->faker->boolean(20) : $this->faker->boolean(50)
            ]
        ];
        
        $info = $caracteristicas[$tipo];
        
        return [
            'duracion' => $this->faker->numberBetween($info['duracion_min'], $info['duracion_max']),
            'precio' => $this->faker->randomFloat(2, $info['precio_min'], $info['precio_max']),
            'es_directo' => $info['es_directo']
        ];
    }

    /**
     * Vuelos domésticos dentro de Ecuador
     */
    public function domestico(): static
    {
        return $this->state(function (array $attributes) {
            // Aeropuertos principales de Ecuador
            $aeropuertosEcuador = ['UIO', 'GYE', 'CUE']; // Quito, Guayaquil, Cuenca
            $origen = $this->faker->randomElement($aeropuertosEcuador);
            
            // Asegurar que destino sea diferente al origen
            $destinosPosibles = array_diff($aeropuertosEcuador, [$origen]);
            $destino = $this->faker->randomElement($destinosPosibles);
            
            // Ajustar duración y precio según la ruta específica
            $duracionYPrecio = $this->obtenerDuracionYPrecioDomestico($origen, $destino);
            
            return [
                'codigo_origen' => $origen,
                'codigo_destino' => $destino,
                'duracion_minutos' => $duracionYPrecio['duracion'],
                'precio_base' => $duracionYPrecio['precio'],
                'es_directo' => true
            ];
        });
    }

    /**
     * Vuelos específicos desde Cuenca
     */
    public function desdeCuenca(): static
    {
        return $this->state(function (array $attributes) {
            $destinosPosibles = ['UIO', 'GYE']; // Quito o Guayaquil
            $destino = $this->faker->randomElement($destinosPosibles);
            
            $duracionYPrecio = $this->obtenerDuracionYPrecioDomestico('CUE', $destino);
            
            return [
                'codigo_origen' => 'CUE',
                'codigo_destino' => $destino,
                'duracion_minutos' => $duracionYPrecio['duracion'],
                'precio_base' => $duracionYPrecio['precio'],
                'es_directo' => true
            ];
        });
    }

    /**
     * Vuelos específicos hacia Cuenca
     */
    public function haciaCuenca(): static
    {
        return $this->state(function (array $attributes) {
            $origenesPosibles = ['UIO', 'GYE']; // Desde Quito o Guayaquil
            $origen = $this->faker->randomElement($origenesPosibles);
            
            $duracionYPrecio = $this->obtenerDuracionYPrecioDomestico($origen, 'CUE');
            
            return [
                'codigo_origen' => $origen,
                'codigo_destino' => 'CUE',
                'duracion_minutos' => $duracionYPrecio['duracion'],
                'precio_base' => $duracionYPrecio['precio'],
                'es_directo' => true
            ];
        });
    }

    /**
     * Obtener duración y precio realistas para rutas domésticas específicas
     */
    private function obtenerDuracionYPrecioDomestico(string $origen, string $destino): array
    {
        // Definir características de cada ruta doméstica
        $rutas = [
            'UIO-GYE' => ['duracion_min' => 50, 'duracion_max' => 70, 'precio_min' => 90, 'precio_max' => 180],   // Quito-Guayaquil
            'GYE-UIO' => ['duracion_min' => 50, 'duracion_max' => 70, 'precio_min' => 90, 'precio_max' => 180],   // Guayaquil-Quito
            'UIO-CUE' => ['duracion_min' => 45, 'duracion_max' => 65, 'precio_min' => 80, 'precio_max' => 160],   // Quito-Cuenca
            'CUE-UIO' => ['duracion_min' => 45, 'duracion_max' => 65, 'precio_min' => 80, 'precio_max' => 160],   // Cuenca-Quito
            'GYE-CUE' => ['duracion_min' => 40, 'duracion_max' => 60, 'precio_min' => 70, 'precio_max' => 150],   // Guayaquil-Cuenca
            'CUE-GYE' => ['duracion_min' => 40, 'duracion_max' => 60, 'precio_min' => 70, 'precio_max' => 150],   // Cuenca-Guayaquil
        ];
        
        $claveRuta = $origen . '-' . $destino;
        $infoRuta = $rutas[$claveRuta] ?? [
            'duracion_min' => 45, 'duracion_max' => 120, 
            'precio_min' => 80, 'precio_max' => 200
        ];
        
        return [
            'duracion' => $this->faker->numberBetween($infoRuta['duracion_min'], $infoRuta['duracion_max']),
            'precio' => $this->faker->randomFloat(2, $infoRuta['precio_min'], $infoRuta['precio_max'])
        ];
    }

    /**
     * Generar código de vuelo realista
     */
    public function conCodigoVuelo(string $codigoAerolinea): static
    {
        return $this->state(function (array $attributes) use ($codigoAerolinea) {
            $numero = $this->faker->numberBetween(100, 9999);
            return [
                'codigo_vuelo' => $codigoAerolinea . $numero
            ];
        });
    }

    /**
     * Configurar asientos disponibles basado en el avión
     */
    public function configurarAsientos(array $configuracionAvion): static
    {
        return $this->state(function (array $attributes) use ($configuracionAvion) {
            $asientosDisponibles = [];
            
            foreach ($configuracionAvion as $clase => $config) {
                // Simular ocupación parcial (entre 20% y 90%)
                $ocupacion = $this->faker->numberBetween(20, 90) / 100;
                $asientosOcupados = (int)($config['total'] * $ocupacion);
                $asientosDisponibles[$clase] = $config['total'] - $asientosOcupados;
            }
            
            return [
                'asientos_disponibles' => $asientosDisponibles
            ];
        });
    }

    /**
     * Generar vuelos para fechas pasadas (ya completados)
     */
    public function vueloPasado(): static
    {
        return $this->state(function (array $attributes) {
            $diasPasados = $this->faker->numberBetween(1, 30);
            $fechaSalida = Carbon::now()->subDays($diasPasados)
                                ->setTime($this->faker->numberBetween(5, 23), $this->faker->randomElement([0, 15, 30, 45]));
            
            $duracion = $this->faker->numberBetween(45, 720);
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);
            
            // Estados apropiados para vuelos pasados
            $estado = $this->faker->randomElement(['aterrizado', 'aterrizado', 'aterrizado', 'cancelado']);
            
            return [
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => $estado
            ];
        });
    }

    /**
     * Generar vuelos para hoy
     */
    public function vueloHoy(): static
    {
        return $this->state(function (array $attributes) {
            $horaAleatoria = $this->faker->numberBetween(5, 23);
            $minutoAleatorio = $this->faker->randomElement([0, 15, 30, 45]);
            
            $fechaSalida = Carbon::today()->setTime($horaAleatoria, $minutoAleatorio);
            $duracion = $this->faker->numberBetween(45, 720);
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);
            
            // Estados apropiados para vuelos de hoy
            $ahora = Carbon::now();
            if ($fechaSalida->isFuture()) {
                $estado = $this->faker->randomElement(['programado', 'en_hora', 'retrasado', 'abordando']);
            } elseif ($fechaLlegada->isFuture()) {
                $estado = $this->faker->randomElement(['en_vuelo', 'abordando']);
            } else {
                $estado = $this->faker->randomElement(['aterrizado', 'aterrizado', 'cancelado']);
            }
            
            return [
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => $estado
            ];
        });
    }

    /**
     * Generar vuelos hasta diciembre con distribución equilibrada
     */
    public function hastaDiciembre(): static
    {
        return $this->state(function (array $attributes) {
            $fechaFin = Carbon::create(2025, 12, 31, 23, 59, 59);
            $diasDisponibles = Carbon::now()->diffInDays($fechaFin);
            
            $diasAleatorios = $this->faker->numberBetween(0, $diasDisponibles);
            $fechaSalida = Carbon::now()->addDays($diasAleatorios)
                                ->setTime($this->faker->numberBetween(5, 23), $this->faker->randomElement([0, 15, 30, 45]));
            
            $duracion = $this->faker->numberBetween(45, 720);
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);
            
            $estado = $this->determinarEstadoVuelo($fechaSalida, $fechaLlegada);
            
            return [
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => $estado
            ];
        });
    }

    /**
     * Generar vuelos con estado específico
     */
    public function conEstado(string $estado): static
    {
        return $this->state(function (array $attributes) use ($estado) {
            // Ajustar fechas según el estado requerido
            switch ($estado) {
                case 'aterrizado':
                    $fechaSalida = Carbon::now()->subHours($this->faker->numberBetween(2, 48));
                    break;
                case 'en_vuelo':
                    $fechaSalida = Carbon::now()->subHours($this->faker->numberBetween(1, 8));
                    break;
                case 'abordando':
                    $fechaSalida = Carbon::now()->addMinutes($this->faker->numberBetween(15, 60));
                    break;
                case 'cancelado':
                    // Los vuelos cancelados pueden ser de cualquier fecha
                    $fechaSalida = Carbon::now()->addDays($this->faker->numberBetween(-5, 30));
                    break;
                default:
                    $fechaSalida = Carbon::now()->addDays($this->faker->numberBetween(1, 90));
            }
            
            $fechaSalida->setTime($this->faker->numberBetween(5, 23), $this->faker->randomElement([0, 15, 30, 45]));
            $duracion = $this->faker->numberBetween(45, 720);
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);
            
            return [
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => $estado
            ];
        });
    }
}