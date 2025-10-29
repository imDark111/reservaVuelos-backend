<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aeropuerto;
use App\Models\Aerolinea;
use App\Models\Avion;
use App\Models\Vuelo;
use Carbon\Carbon;

class ReservaVuelosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌍 Creando aeropuertos...');
        
        // Crear aeropuertos usando factory (incluye Ecuador + destinos internacionales)
        Aeropuerto::factory()->count(15)->create();

        $this->command->info('✈️ Creando aerolíneas...');
        
        // Crear aerolíneas usando factory
        $aerolineas = Aerolinea::factory()->count(8)->create();

        $this->command->info('🛩️ Creando aviones...');
        
        // Crear aviones para cada aerolínea
        $aerolineas->each(function ($aerolinea) {
            // Cada aerolínea tiene entre 2-5 aviones
            $cantidadAviones = rand(2, 5);
            
            for ($i = 0; $i < $cantidadAviones; $i++) {
                Avion::factory()
                    ->forAerolinea($aerolinea)
                    ->create();
            }
        });

        $this->command->info('🎫 Creando vuelos desde Ecuador...');
        
        // Obtener datos necesarios
        $aeropuertosEcuador = Aeropuerto::whereIn('codigo_iata', ['UIO', 'GYE', 'CUE'])->get();
        $aeropuertosDestino = Aeropuerto::whereNotIn('codigo_iata', ['UIO', 'GYE', 'CUE'])->get();
        $aviones = Avion::all();
        $aerolineas = Aerolinea::all();

        // Verificar que tenemos datos
        if ($aeropuertosEcuador->isEmpty() || $aeropuertosDestino->isEmpty() || $aviones->isEmpty()) {
            $this->command->error('❌ No hay suficientes datos. Verifica que los seeders anteriores hayan corrido.');
            return;
        }

        // Crear vuelos desde Ecuador hacia destinos internacionales
        for ($i = 0; $i < 50; $i++) {
            $origen = $aeropuertosEcuador->random();
            $destino = $aeropuertosDestino->random();
            $avion = $aviones->random();
            $aerolinea = $aerolineas->find($avion->aerolinea_id);

            // Generar código de vuelo basado en la aerolínea
            $codigoAerolinea = $aerolinea ? $aerolinea->codigo_iata : 'XX';
            $codigoVuelo = $codigoAerolinea . rand(100, 999);

            // Determinar tipo de vuelo y ajustar duración/precio
            $esIntercontinental = in_array($destino->codigo_iata, ['MAD', 'AMS', 'JFK']);
            $esRegional = in_array($destino->codigo_iata, ['BOG', 'LIM', 'PTY', 'CCS']);

            if ($esIntercontinental) {
                $duracion = rand(720, 900); // 12-15 horas
                $precioBase = rand(1200, 2500);
            } elseif ($esRegional) {
                $duracion = rand(180, 420); // 3-7 horas
                $precioBase = rand(300, 800);
            } else {
                $duracion = rand(240, 600); // 4-10 horas
                $precioBase = rand(500, 1200);
            }

            // Fecha de salida aleatoria en los próximos 60 días
            $fechaSalida = Carbon::now()->addDays(rand(1, 60))
                                ->setTime(rand(5, 23), collect([0, 15, 30, 45])->random());
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);

            // Configurar tarifas
            $tarifas = [
                'economica' => $precioBase,
                'premium' => round($precioBase * 1.8, 2),
                'ejecutiva' => round($precioBase * 3.2, 2)
            ];

            // Solo agregar primera clase para vuelos intercontinentales
            if ($esIntercontinental) {
                $tarifas['primera'] = round($precioBase * 5.5, 2);
            }

            // Configurar asientos disponibles basado en la configuración del avión
            $asientosDisponibles = [];
            foreach ($avion->configuracion_asientos as $clase => $config) {
                $ocupacion = rand(20, 80) / 100; // 20-80% de ocupación
                $asientosOcupados = (int)($config['total'] * $ocupacion);
                $asientosDisponibles[$clase] = $config['total'] - $asientosOcupados;
            }

            Vuelo::create([
                'codigo_vuelo' => $codigoVuelo,
                'avion_id' => $avion->_id,
                'origen_id' => $origen->_id,
                'destino_id' => $destino->_id,
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => collect(['programado', 'en_hora'])->random(),
                'precio_base' => $precioBase,
                'tarifas' => $tarifas,
                'asientos_disponibles' => $asientosDisponibles,
                'es_directo' => rand(1, 100) <= 85, // 85% de vuelos directos
                'activo' => true
            ]);
        }

        // Crear algunos vuelos domésticos dentro de Ecuador
        $this->command->info('🏠 Creando vuelos domésticos...');
        
        for ($i = 0; $i < 15; $i++) {
            $origen = $aeropuertosEcuador->random();
            $destino = $aeropuertosEcuador->where('_id', '!=', $origen->_id)->random();
            $avion = $aviones->random();
            $aerolinea = $aerolineas->find($avion->aerolinea_id);

            $codigoAerolinea = $aerolinea ? $aerolinea->codigo_iata : 'XX';
            $codigoVuelo = $codigoAerolinea . rand(1000, 1999);
            $duracion = rand(45, 120); // 45min - 2h
            $precioBase = rand(80, 200);

            $fechaSalida = Carbon::now()->addDays(rand(1, 30))
                                ->setTime(rand(6, 22), collect([0, 30])->random());
            $fechaLlegada = $fechaSalida->copy()->addMinutes($duracion);

            $tarifas = [
                'economica' => $precioBase,
                'ejecutiva' => round($precioBase * 2.5, 2)
            ];

            // Asientos para vuelos domésticos (solo económica y ejecutiva)
            $asientosDisponibles = [];
            foreach (['economica', 'ejecutiva'] as $clase) {
                if (isset($avion->configuracion_asientos[$clase])) {
                    $config = $avion->configuracion_asientos[$clase];
                    $ocupacion = rand(30, 90) / 100;
                    $asientosOcupados = (int)($config['total'] * $ocupacion);
                    $asientosDisponibles[$clase] = $config['total'] - $asientosOcupados;
                }
            }

            Vuelo::create([
                'codigo_vuelo' => $codigoVuelo,
                'avion_id' => $avion->_id,
                'origen_id' => $origen->_id,
                'destino_id' => $destino->_id,
                'fecha_salida' => $fechaSalida,
                'fecha_llegada' => $fechaLlegada,
                'duracion_minutos' => $duracion,
                'estado_vuelo' => 'programado',
                'precio_base' => $precioBase,
                'tarifas' => $tarifas,
                'asientos_disponibles' => $asientosDisponibles,
                'es_directo' => true,
                'activo' => true
            ]);
        }

        $this->command->info('✅ Seeding completado!');
        $this->command->table(
            ['Modelo', 'Cantidad Creada'],
            [
                ['Aeropuertos', Aeropuerto::count()],
                ['Aerolíneas', Aerolinea::count()],
                ['Aviones', Avion::count()],
                ['Vuelos', Vuelo::count()],
            ]
        );
    }
}