<?php
session_start();
require_once '../src/config/database.php';

if (
    !isset($_SESSION['evaluacion_id']) ||
    !isset($_SESSION['evaluador_nombre']) ||
    !isset($_SESSION['evaluador_relacion'])
) {
    header("Location: index.php");
    exit;
}

$evaluacion_id = $_SESSION['evaluacion_id'];
$nombre = $_SESSION['evaluador_nombre'];
$relacion = $_SESSION['evaluador_relacion'];
$mensaje = '';

// Guardar respuestas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respuestas'])) {
    $tabla_respuesta = ($relacion === 'Autoevaluación') ? 'autoevaluaciones' : 'respuestas';

    foreach ($_POST['respuestas'] as $pregunta_id => $valor) {
        if ($tabla_respuesta === 'autoevaluaciones') {
            $stmt = $conexion->prepare("INSERT INTO autoevaluaciones (evaluacion_id, evaluacion_pregunta_id, valor) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $evaluacion_id, $pregunta_id, $valor);
        } else {
            $stmt = $conexion->prepare("INSERT INTO respuestas (evaluacion_id, evaluacion_pregunta_id, valor, evaluador_nombre, relacion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $evaluacion_id, $pregunta_id, $valor, $nombre, $relacion);
        }
        $stmt->execute();
    }

    $mensaje = "Respuestas guardadas correctamente.";
}

// Asignar preguntas si no existen
$stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM evaluacion_preguntas WHERE evaluacion_id = ?");
$stmt_check->bind_param("i", $evaluacion_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result()->fetch_assoc();

if ($result_check['total'] == 0) {
    $stmt_p = $conexion->query("SELECT id FROM preguntas ORDER BY RAND()");
    $stmt_insert = $conexion->prepare("INSERT INTO evaluacion_preguntas (evaluacion_id, pregunta_id) VALUES (?, ?)");
    while ($row = $stmt_p->fetch_assoc()) {
        $stmt_insert->bind_param("ii", $evaluacion_id, $row['id']);
        $stmt_insert->execute();
    }
}

// Obtener preguntas pendientes agrupadas por dimensión
$tabla_respuesta = ($relacion === 'Autoevaluación') ? 'autoevaluaciones' : 'respuestas';

$query = "
    SELECT ep.id AS ep_id, p.texto, d.nombre AS dimension
    FROM evaluacion_preguntas ep
    JOIN preguntas p ON ep.pregunta_id = p.id
    JOIN dimensiones d ON p.dimension_id = d.id
    WHERE ep.evaluacion_id = ?
    AND ep.id NOT IN (
        SELECT evaluacion_pregunta_id
        FROM $tabla_respuesta
        WHERE evaluacion_id = ?" . ($relacion === 'Autoevaluación' ? "" : " AND evaluador_nombre = ? AND relacion = ?") . "
    )
    ORDER BY d.nombre, ep.id
";

if ($relacion === 'Autoevaluación') {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $evaluacion_id, $evaluacion_id);
} else {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iiss", $evaluacion_id, $evaluacion_id, $nombre, $relacion);
}

$stmt->execute();
$result = $stmt->get_result();

$preguntas_por_area = [];
while ($row = $result->fetch_assoc()) {
    $preguntas_por_area[$row['dimension']][] = $row;
}

$total_pendientes = 0;
foreach ($preguntas_por_area as $bloque) {
    $total_pendientes += count($bloque);
}

if ($total_pendientes === 0) {
    unset($_SESSION['evaluacion_id'], $_SESSION['evaluador_nombre'], $_SESSION['evaluador_relacion'], $_SESSION['codigo_temp']);
    header("Location: gracias.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluación por Áreas</title>
    <link rel="stylesheet" href="assets/css/evaluar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .info, .escalas, .formulario {
            background: #ffffff;
            padding: 20px;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: left;
            max-width: 800px;
            font-size: 16px;
        }
        .area {
            margin-top: 40px;
        }
        .area h3 {
            color: #1abc9c;
            margin-bottom: 10px;
        }
        .pregunta {
            background: #fefefe;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .slider-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
            font-weight: bold;
            margin-top: 8px;
            color: #333;
        }
        input[type=range] {
            width: 100%;
            appearance: none;
            height: 12px;
            border-radius: 6px;
            background: linear-gradient(to right, red, orange, yellow, lightgreen, green);
            outline: none;
        }
        input[type=range]::-webkit-slider-thumb {
            width: 22px;
            height: 22px;
            background: #2c3e50;
            border-radius: 50%;
            border: 2px solid white;
            cursor: pointer;
            margin-top: -6px;
            box-shadow: 0 0 2px rgba(0, 0, 0, 0.3);
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 30px;
            font-size: 16px;
        }
        button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>

    <h2>Evaluación en Progreso</h2>

    <div class="info">
        <?php if (isset($_SESSION['codigo_temp'])): ?>
            <p><strong>Código temporal:</strong> <?= htmlspecialchars($_SESSION['codigo_temp']) ?></p>
        <?php endif; ?>
        <p><strong>Relación:</strong> <?= htmlspecialchars($relacion) ?></p>
        <p><strong>Preguntas pendientes:</strong> <?= $total_pendientes ?></p>
        <?php if ($mensaje): ?>
            <p style="color: green;"><strong><?= $mensaje ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="escalas">
        <p><strong>Escala de respuesta:</strong></p>
        <table>
            <tr><th>Número</th><th>Significado</th></tr>
            <tr><td>1</td><td>Nunca</td></tr>
            <tr><td>2</td><td>Casi nunca</td></tr>
            <tr><td>3</td><td>Ocasionalmente</td></tr>
            <tr><td>4</td><td>A menudo</td></tr>
            <tr><td>5</td><td>Casi siempre</td></tr>
            <tr><td>6</td><td>Siempre</td></tr>
        </table>
    </div>

    <form method="POST" class="formulario">
        <?php foreach ($preguntas_por_area as $dimension => $preguntas): ?>
            <div class="area">
                <h3><?= htmlspecialchars($dimension) ?></h3>
                <?php foreach ($preguntas as $p): ?>
                    <div class="pregunta">
                        <p><strong><?= htmlspecialchars($p['texto']) ?></strong></p>
                        <div class="slider-container">
                            <input type="range" name="respuestas[<?= $p['ep_id'] ?>]" min="1" max="6" step="1" required>
                        </div>
                        <div class="slider-labels">
                            <span>1</span><span>2</span><span>3</span>
                            <span>4</span><span>5</span><span>6</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit">Guardar respuestas</button>
    </form>

</body>
</html>
