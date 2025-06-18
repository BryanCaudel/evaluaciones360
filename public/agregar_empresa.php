<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: index.php");
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        $stmt = $conexion->prepare("INSERT INTO empresas (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        if ($stmt->execute()) {
            $mensaje = "Empresa agregada correctamente.";
        } else {
            $mensaje = "Error al guardar empresa.";
        }
    } else {
        $mensaje = "El nombre es obligatorio.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Empresa</title>
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <div class="form-container">
        <h2>Agregar Nueva Empresa</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="nombre">Nombre de la empresa:</label>
            <input type="text" name="nombre" id="nombre" required>

            <button type="submit">Guardar Empresa</button>
        </form>
<br>
        <!-- Botón volver -->
        <div class="volver-container">
            <a href="dashboard.php" class="btn-volver">← Volver</a>
        </div>
    </div>
</body>
</html>
