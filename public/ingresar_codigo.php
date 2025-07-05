<?php
session_start();
require_once '../src/config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];

    $stmt = $conexion->prepare("SELECT id FROM evaluaciones WHERE codigo_unico = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $evaluacion = $result->fetch_assoc();
        $_SESSION['evaluacion_id'] = $evaluacion['id'];
        header("Location: evaluar_datos.php");
        exit;
    } else {
        $mensaje = "Código de evaluación no válido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresar Código</title>
    <link rel="stylesheet" href="assets/css/evaluar.css">
</head>
<body>
    <h2>Evaluación Anónima</h2>

    <form method="POST">
        <label>Introduce tu código de evaluación:</label>
        <input type="text" name="codigo" required>
        <button type="submit">Ingresar</button>

        <?php if ($mensaje): ?>
            <p class="error"><?= $mensaje ?></p>
        <?php endif; ?>
    </form>

    <div style="text-align: center; margin-top: 40px;">
        <a href="continuar_evaluacion.php" style="
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        ">
            ¿Ya comenzaste? Continuar evaluación
        </a>
    </div>
</body>
</html>
