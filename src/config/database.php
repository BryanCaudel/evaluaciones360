<?php
$host = getenv("DB_HOST") ?: 'localhost';
$user = getenv("DB_USER") ?: 'root';
$password = getenv("DB_PASSWORD") ?: '';
$database = getenv("DB_NAME") ?: 'demo';

$conexion = @mysqli_connect($host, $user, $password, $database);

if (!$conexion) {
    echo "<div style='background-color:#ffeeaa; padding:10px; border:1px solid #ccaa00; font-family:sans-serif; text-align:center;'>
            ⚠️ <strong>Modo Demostración:</strong> No se pudo conectar a la base de datos. Esta versión es solo visual para presentación.
          </div>";
    // Para evitar errores en otras partes, usamos una conexión simulada
    $conexion = true;
}
?>
