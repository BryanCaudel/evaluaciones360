<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_NAME");

// Si no hay variables definidas, se asume modo demostración
if (!$host || !$user || !$database) {
    echo "<div style='background-color:#ffeeaa; padding:10px; border:1px solid #ccaa00; font-family:sans-serif; text-align:center;'>
            ⚠️ <strong>Modo Demostración:</strong> Esta versión no tiene conexión a base de datos y es solo para presentación visual.
          </div>";

    // Simulamos una conexión para evitar errores en otras partes
    $conexion = true;
    return;
}

// Intentar conexión real (en caso de que sí haya BD)
$conexion = @mysqli_connect($host, $user, $password, $database);

if (!$conexion) {
    echo "<div style='background-color:#ffeeaa; padding:10px; border:1px solid #ccaa00; font-family:sans-serif; text-align:center;'>
            ⚠️ <strong>Modo Demostración:</strong> No se pudo conectar a la base de datos. La app funciona solo como vista previa.
          </div>";
    $conexion = true;
}
?>
