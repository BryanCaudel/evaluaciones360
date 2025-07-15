<?php 
$host = 'db';
$user = 'root';
$password = ''; // sin contraseña
$database = 'u422899116_evaluacion360';

$intentos = 10;
while ($intentos > 0) {
    $conexion = @mysqli_connect($host, $user, $password, $database);
    if ($conexion) break;
    echo "Esperando conexión con la base de datos...<br>";
    sleep(2);
    $intentos--;
}

if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}
?>
