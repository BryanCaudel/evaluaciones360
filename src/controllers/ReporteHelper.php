<?php

function obtenerDatosReporte($conexion, $evaluacion_id) {
    $datos = [];

    // Obtener promedios por dimensión
    $stmt = $conexion->prepare("
        SELECT d.nombre AS dimension, r.relacion, r.promedio
        FROM resultados r
        INNER JOIN dimensiones d ON r.dimension_id = d.id
        WHERE r.evaluacion_id = ?
    ");
    $stmt->bind_param("i", $evaluacion_id);
    $stmt->execute();
    $resDimension = $stmt->get_result();

    $datos['por_dimension'] = [];
    $datos['personal'] = [];
    $datos['social'] = [];

    while ($row = $resDimension->fetch_assoc()) {
        $dim = $row['dimension'];
        $rel = $row['relacion'];
        $prom = (float)$row['promedio'];
        $datos['por_dimension'][$dim][$rel] = $prom;

        if (in_array($dim, ['Congruencia Personal', 'Balance entre la Vida Personal y Laboral'])) {
            $datos['personal'][$dim][$rel] = $prom;
        } else {
            $datos['social'][$dim][$rel] = $prom;
        }
    }

    // Obtener promedios por pregunta
    $stmt2 = $conexion->prepare("
        SELECT d.nombre AS dimension, p.texto, r.relacion, ROUND(AVG(r.valor),2) AS promedio
        FROM respuestas r
        INNER JOIN evaluacion_preguntas ep ON r.evaluacion_pregunta_id = ep.id
        INNER JOIN preguntas p ON ep.pregunta_id = p.id
        INNER JOIN dimensiones d ON p.dimension_id = d.id
        WHERE r.evaluacion_id = ?
        GROUP BY ep.id, r.relacion
    ");
    $stmt2->bind_param("i", $evaluacion_id);
    $stmt2->execute();
    $resPreguntas = $stmt2->get_result();

    $datos['por_pregunta'] = [];

    while ($row = $resPreguntas->fetch_assoc()) {
        $dim = $row['dimension'];
        $preg = $row['texto'];
        $rel = $row['relacion'];
        $prom = (float)$row['promedio'];
        $datos['por_pregunta'][$dim][$preg][$rel] = $prom;
    }

    return $datos;
}

function generarResumenGlobal($datos) {
    ob_start();
    echo "<table><tr><th>Dimensión</th><th>Jefe</th><th>Colega</th><th>Colaborador</th></tr>";
    foreach ($datos['por_dimension'] as $dim => $valores) {
        echo "<tr><td>$dim</td>";
        foreach (['Jefe', 'Colega', 'Colaborador'] as $tipo) {
            echo "<td>" . ($valores[$tipo] ?? '-') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return ob_get_clean();
}

function generarTablaCapital($datosCapital) {
    ob_start();
    echo "<table><tr><th>Dimensión</th><th>Jefe</th><th>Colega</th><th>Colaborador</th></tr>";
    foreach ($datosCapital as $dim => $valores) {
        echo "<tr><td>$dim</td>";
        foreach (['Jefe', 'Colega', 'Colaborador'] as $tipo) {
            echo "<td>" . ($valores[$tipo] ?? '-') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    return ob_get_clean();
}

function generarResumenPorDimension($datos) {
    return generarResumenGlobal(['por_dimension' => $datos]);
}

function generarTablaPorPregunta($datos) {
    ob_start();
    foreach ($datos as $dim => $preguntas) {
        echo "<h3>$dim</h3><table><tr><th>Pregunta</th><th>Jefe</th><th>Colega</th><th>Colaborador</th></tr>";
        foreach ($preguntas as $preg => $valores) {
            echo "<tr><td>$preg</td>";
            foreach (['Jefe', 'Colega', 'Colaborador'] as $tipo) {
                echo "<td>" . ($valores[$tipo] ?? '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    return ob_get_clean();
}

function generarDescripcionesDimensiones($conexion) {
    // Aquí puedes meter descripciones manuales o agregar una tabla `descripcion_dimensiones`
    $dimensiones = [
        'Enfoque Proactivo' => 'Capacidad de anticiparse a los problemas y actuar con iniciativa.',
        'Congruencia Personal' => 'Coherencia entre lo que se dice, piensa y hace.',
        'Colaboración' => 'Trabajo en equipo y cooperación con los demás.',
        // Agrega todas las demás descripciones aquí
    ];

    ob_start();
    foreach ($dimensiones as $nombre => $desc) {
        echo "<h4>$nombre</h4><p>$desc</p>";
    }
    return ob_get_clean();
}
