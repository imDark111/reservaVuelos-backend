<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$reservas = App\Models\Reserva::all();
echo "Reservas en BD:\n";
foreach($reservas as $r) {
    echo $r->_id . '|' . $r->usuario_id . '|' . $r->estado . "\n";
}