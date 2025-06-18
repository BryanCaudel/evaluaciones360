<?php 
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['evaluacion_id'])) {
    header("Location: evaluar.php");
    exit;
}

$evaluacion_id = $_SESSION['evaluacion_id'];

// Obtener nombre real del evaluado
$stmt = $conexion->prepare("
    SELECT u.user 
    FROM evaluaciones e 
    LEFT JOIN usuarios u ON e.evaluado_id = u.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $evaluacion_id);
$stmt->execute();
$resultado = $stmt->get_result();
$evaluado = $resultado->fetch_assoc();
$nombre_evaluado = $evaluado ? $evaluado['user'] : 'Evaluado';

// Verificar si ya existe una autoevaluación
$stmt_check = $conexion->prepare("SELECT 1 FROM autoevaluaciones WHERE evaluacion_id = ? LIMIT 1");
$stmt_check->bind_param("i", $evaluacion_id);
$stmt_check->execute();
$autoeval_realizada = $stmt_check->get_result()->num_rows > 0;

$mensaje = '';
$codigo_generado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $relacion = $_POST['relacion'];

    if (in_array($relacion, ['Jefe', 'Colega', 'Colaborador', 'Autoevaluación'])) {
        if ($relacion === 'Autoevaluación') {
            if ($autoeval_realizada) {
                $mensaje = "Ya se ha realizado una autoevaluación para este evaluado.";
            } else {
                $codigo_temp = "AUTO-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

                $_SESSION['evaluador_nombre'] = 'Autoevaluación';
                $_SESSION['evaluador_relacion'] = 'Autoevaluación';
                $_SESSION['codigo_temp'] = $codigo_temp;
                $codigo_generado = $codigo_temp;

                // Guardar código en sesiones_evaluadores por compatibilidad
                $stmt = $conexion->prepare("INSERT INTO sesiones_evaluadores (codigo_temp, evaluacion_id, nombre, relacion) VALUES (?, ?, ?, ?)");
                $nombre_auto = 'Autoevaluación';
                $stmt->bind_param("siss", $codigo_temp, $evaluacion_id, $nombre_auto, $relacion);
                $stmt->execute();

                header("Location: evaluar_preguntas.php");
                exit;
            }
        } else {
            // Generar nombre anónimo único
            $nombre_anonimo = "Anon_" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $codigo_temp = "TEMP-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

            $_SESSION['evaluador_nombre'] = $nombre_anonimo;
            $_SESSION['evaluador_relacion'] = $relacion;
            $_SESSION['codigo_temp'] = $codigo_temp;
            $codigo_generado = $codigo_temp;

            $stmt = $conexion->prepare("INSERT INTO sesiones_evaluadores (codigo_temp, evaluacion_id, nombre, relacion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $codigo_temp, $evaluacion_id, $nombre_anonimo, $relacion);
            $stmt->execute();

            header("Location: evaluar_preguntas.php");
            exit;
        }
    } else {
        $mensaje = "Debes seleccionar una relación válida.";
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
    <div class="form-container">
        <h2>Evaluando a: <span><?= htmlspecialchars($nombre_evaluado) ?></span></h2>

        <?php if ($mensaje): ?>
            <p class="error"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <?php if ($codigo_generado): ?>
            <p><strong>Tu código temporal para continuar la evaluación es:</strong><br>
            <span style="font-size: 18px; color: #2c3e50; background: #ecf0f1; padding: 6px 12px; border-radius: 4px;">
                <?= htmlspecialchars($codigo_generado) ?>
            </span></p>
        <?php endif; ?>

        <form method="POST">
            <label style="text-align: center;">Selecciona tu relación con el evaluado</label>
            <div style="display: grid; grid-template-columns: 15% 85%;">
                <p>Soy su:</p>
            <select name="relacion" required>
                <option value="">Selecciona</option>
                <?php if (!$autoeval_realizada): ?>
                    <option value="Autoevaluación">Autoevaluación</option>
                <?php endif; ?>
                <option value="Jefe">Jefe</option>
                <option value="Colega">Colega</option>
                <option value="Colaborador">Colaborador</option>
            </select>
                </div>
            <button type="submit">Comenzar Evaluación</button>
        </form>
    </div>
</body>
</html>
