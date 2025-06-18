<?php
require "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluadoID'])) {
    $evaluadoID = 10;

    // Promedio por dimensión
    $queryDim = $db->prepare("
        SELECT d.NombreDimension, AVG(r.Puntuacion) AS Promedio
        FROM respuestas r
        JOIN preguntas p ON r.PreguntaID = p.PreguntaID
        JOIN dimensiones d ON p.DimensionID = d.DimensionID
        WHERE r.EvaluadoID = ?
        GROUP BY d.NombreDimension
    ");
    $queryDim->bind_param("i", $evaluadoID);
    $queryDim->execute();
    $resultDim = $queryDim->get_result();
    $promedioPorDimension = $resultDim->fetch_all(MYSQLI_ASSOC);

    // Comparativa por tipo de evaluador
    $queryComp = $db->prepare("
        SELECT r.EvaluadorAnonimoID, u.user AS EvaluadorTipo, AVG(r.Puntuacion) AS Promedio
        FROM respuestas r
        JOIN usuarios u ON r.EvaluadorAnonimoID = u.UsuarioID
        WHERE r.EvaluadoID = ?
        GROUP BY r.EvaluadorAnonimoID
    ");
    $queryComp->bind_param("i", $evaluadoID);
    $queryComp->execute();
    $resultComp = $queryComp->get_result();
    $comparativaPorEvaluador = $resultComp->fetch_all(MYSQLI_ASSOC);

    // Distribución de puntuaciones
    $queryDist = $db->prepare("
        SELECT r.Puntuacion, COUNT(*) AS Cantidad
        FROM respuestas r
        WHERE r.EvaluadoID = ?
        GROUP BY r.Puntuacion
    ");
    $queryDist->bind_param("i", $evaluadoID);
    $queryDist->execute();
    $resultDist = $queryDist->get_result();
    $distribucionPuntuaciones = $resultDist->fetch_all(MYSQLI_ASSOC);

    // Respuesta
    header('Content-Type: application/json');
    echo json_encode([
        'promedioPorDimension' => $promedioPorDimension,
        'comparativaPorEvaluador' => $comparativaPorEvaluador,
        'distribucionPuntuaciones' => $distribucionPuntuaciones,
    ]);
    exit;
}
?>
