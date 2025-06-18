<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['evaluacion_id'])) {
    header("Location: evaluar.php");
    exit;
}

$evaluacion_id = $_SESSION['evaluacion_id'];

// Obtener nombre real del evaluado desde la tabla usuarios usando evaluado_id
$stmt = $conexion->prepare("
    SELECT u.user AS nombre_real
    FROM evaluaciones e
    JOIN usuarios u ON e.evaluado_id = u.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $evaluacion_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$nombre_evaluado = $data['nombre_real'] ?? 'Desconocido';

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $relacion = $_POST['relacion'];

    if ($nombre && in_array($relacion, ['Jefe', 'Colega', 'Colaborador'])) {
        $codigo_temp = "TEMP-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

        $_SESSION['evaluador_nombre'] = $nombre;
        $_SESSION['evaluador_relacion'] = $relacion;
        $_SESSION['codigo_temp'] = $codigo_temp;

        // Guardar en la tabla de sesiones
        $stmt = $conexion->prepare("INSERT INTO sesiones_evaluadores (codigo_temp, evaluacion_id, nombre, relacion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $codigo_temp, $evaluacion_id, $nombre, $relacion);
        $stmt->execute();

        header("Location: evaluar_preguntas.php");
        exit;
    } else {
        $mensaje = "Completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio Evaluación</title>
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <h2>Evaluando a: <?= htmlspecialchars($nombre_evaluado) ?></h2>

    <?php if ($mensaje): ?>
        <p style="color:red;"><?= $mensaje ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Tu nombre:</label>
        <input type="text" name="nombre" required>

        <label>Relación con el evaluado:</label>
        <select name="relacion" required>
            <option value="">Selecciona</option>
            <option value="Jefe">Jefe</option>
            <option value="Colega">Colega</option>
            <option value="Colaborador">Colaborador</option>
        </select>

        <button type="submit">Iniciar</button>
    </form>
</body>
</html>
