<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Reserva;

// Verificar usuarios
$usuarios = User::all();
echo "Usuarios en BD:\n";
foreach($usuarios as $u) {
    echo $u->_id . '|' . $u->email . '|' . $u->name . "\n";
}

echo "\nReservas en BD:\n";
$reservas = Reserva::all();
foreach($reservas as $r) {
    echo $r->_id . '|' . $r->usuario_id . '|' . $r->estado . "\n";
}