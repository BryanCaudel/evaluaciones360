<?php
session_start();
require_once '../src/config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_temp'])) {
    $codigo = $_POST['codigo_temp'];

    $stmt = $conexion->prepare("SELECT * FROM sesiones_evaluadores WHERE codigo_temp = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $sesion = $resultado->fetch_assoc();

        $_SESSION['evaluacion_id'] = $sesion['evaluacion_id'];
        $_SESSION['evaluador_nombre'] = $sesion['nombre'];
        $_SESSION['evaluador_relacion'] = $sesion['relacion'];
        $_SESSION['codigo_temp'] = $sesion['codigo_temp'];

        header("Location: evaluar_preguntas.php");
        exit;
    } else {
        $mensaje = "Código no válido. Intenta nuevamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Continuar Evaluación</title>
    <link rel="stylesheet" href="assets/css/continuar.css">
</head>
<body>
    <div class="form-container">
        <h2>Continuar Evaluación</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="error"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="codigo_temp">Introduce tu código temporal:</label>
            <input type="text" name="codigo_temp" id="codigo_temp" required>
            <button type="submit">Continuar</button>
        </form>
    </div>
</body>
</html>
