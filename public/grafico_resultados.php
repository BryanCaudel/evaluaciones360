<?php 
require_once '../src/config/database.php';

if (!isset($_GET['evaluacion_id'])) {
    echo "ID del evaluado no proporcionado.";
    exit;
}

$evaluacion_id = $_GET['evaluacion_id'];

// Obtener nombre del evaluado desde 'user'
$stmt_nombre = $conexion->prepare("
    SELECT u.user AS nombre
    FROM evaluaciones e
    INNER JOIN usuarios u ON e.evaluado_id = u.id
    WHERE e.id = ?
");
$stmt_nombre->bind_param("i", $evaluacion_id);
$stmt_nombre->execute();
$res_nombre = $stmt_nombre->get_result()->fetch_assoc();
$nombre_evaluado = $res_nombre['nombre'] ?? 'Desconocido';

// Obtener dimensiones
$dimensiones = [];
$stmt_dim = $conexion->prepare("SELECT id, nombre FROM dimensiones");
$stmt_dim->execute();
$res_dim = $stmt_dim->get_result();
while ($row = $res_dim->fetch_assoc()) {
    $dimensiones[] = $row;
}

$data = [];
$totales = ['Autoevaluación' => [], 'Jefe' => [], 'Colega' => [], 'Colaborador' => []];

foreach ($dimensiones as $dim) {
    $dimension_id = $dim['id'];
    $nombre_dim = $dim['nombre'];

    $stmt_preg = $conexion->prepare("
        SELECT ep.id AS eval_pregunta_id, p.texto
        FROM evaluacion_preguntas ep
        INNER JOIN preguntas p ON ep.pregunta_id = p.id
        WHERE p.dimension_id = ? AND ep.evaluacion_id = ?
    ");
    $stmt_preg->bind_param("ii", $dimension_id, $evaluacion_id);
    $stmt_preg->execute();
    $res_preg = $stmt_preg->get_result();

    $preguntas = [];
    while ($preg = $res_preg->fetch_assoc()) {
        $auto = obtener_promedio($conexion, 'autoevaluaciones', $evaluacion_id, $preg['eval_pregunta_id']);
        $jefe = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Jefe');
        $colega = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colega');
        $colaborador = obtener_promedio_relacion($conexion, $evaluacion_id, $preg['eval_pregunta_id'], 'Colaborador');

        $totales['Autoevaluación'][] = $auto;
        $totales['Jefe'][] = $jefe;
        $totales['Colega'][] = $colega;
        $totales['Colaborador'][] = $colaborador;

        $preguntas[] = [
            'texto' => $preg['texto'],
            'auto' => $auto,
            'Jefe' => $jefe,
            'Colega' => $colega,
            'Colaborador' => $colaborador
        ];
    }

    if (!empty($preguntas)) {
        $data[] = [
            'nombre' => $nombre_dim,
            'preguntas' => $preguntas
        ];
    }
}

function obtener_promedio($conexion, $tabla, $evaluacion_id, $eval_pregunta_id) {
    $stmt = $conexion->prepare("SELECT AVG(valor) AS promedio FROM $tabla WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ?");
    $stmt->bind_param("ii", $evaluacion_id, $eval_pregunta_id);
    $stmt->execute();
    return round($stmt->get_result()->fetch_assoc()['promedio'] ?? 0, 2);
}

function obtener_promedio_relacion($conexion, $evaluacion_id, $eval_pregunta_id, $relacion) {
    $stmt = $conexion->prepare("SELECT AVG(valor) AS promedio FROM respuestas WHERE evaluacion_id = ? AND evaluacion_pregunta_id = ? AND relacion = ?");
    $stmt->bind_param("iis", $evaluacion_id, $eval_pregunta_id, $relacion);
    $stmt->execute();
    return round($stmt->get_result()->fetch_assoc()['promedio'] ?? 0, 2);
}

function calcular_promedio_general($array) {
    $filtrados = array_filter($array, fn($v) => $v > 0);
    if (count($filtrados) === 0) return 'N/A';
    return round(array_sum($filtrados) / count($filtrados), 2);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte por Tablas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            text-align: center;
            color: #2c3e50;
        }
        .resumen {
            background: #fff;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .tabla-container {
            background: #fff;
            padding: 20px;
            margin-bottom: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f0f0f0;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
    </style>
</head>
<body>

    <h1>Evaluación 360°</h1>
    <div class="resumen">
        <h2>Resumen General</h2>
        <p><strong>Evaluado:</strong> <?= htmlspecialchars($nombre_evaluado) ?></p>
        <p><strong>Promedio Autoevaluación:</strong> <?= calcular_promedio_general($totales['Autoevaluación']) ?></p>
        <p><strong>Promedio Jefe:</strong> <?= calcular_promedio_general($totales['Jefe']) ?></p>
        <p><strong>Promedio Colega:</strong> <?= calcular_promedio_general($totales['Colega']) ?></p>
        <p><strong>Promedio Colaborador:</strong> <?= calcular_promedio_general($totales['Colaborador']) ?></p>
    </div>

    <?php foreach ($data as $dim): ?>
        <div class="tabla-container">
            <h2><?= htmlspecialchars($dim['nombre']) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Pregunta</th>
                        <th>Autoevaluación</th>
                        <th>Jefe</th>
                        <th>Colega</th>
                        <th>Colaborador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dim['preguntas'] as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['texto']) ?></td>
                            <td><?= $p['auto'] ?></td>
                            <td><?= $p['Jefe'] ?></td>
                            <td><?= $p['Colega'] ?></td>
                            <td><?= $p['Colaborador'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

</body>
</html>
