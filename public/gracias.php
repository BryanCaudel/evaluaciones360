<?php 
session_start();
require_once '../src/config/database.php';

$mostrarBotonGrafico = false;
$evaluacion_id = null;

// Solo mostrar el gráfico si es un usuario con rol 'evaluado' que inició sesión
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'evaluado' && isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];

    // Buscar evaluación asociada a ese usuario
    $stmt = $conexion->prepare("SELECT id FROM evaluaciones WHERE evaluado_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $evaluacion_id = $row['id'];
        $_SESSION['evaluacion_id'] = $evaluacion_id;
        $mostrarBotonGrafico = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>¡Gracias por tu evaluación!</title>
    <link rel="stylesheet" href="assets/css/gracias.css">
</head>
<body>
    <div class="contenedor">
        <h1>¡Gracias por completar la evaluación!</h1>
        <p>Tu retroalimentación ha sido registrada exitosamente.</p>

        <?php if ($mostrarBotonGrafico && $evaluacion_id): ?>
            <form action="grafico_resultados.php" method="post" style="display:inline;">
                <input type="hidden" name="evaluacion_id" value="<?= $evaluacion_id ?>">
                <button type="submit" class="boton">Ver mi gráfico de evaluación</button>
            </form>
        <?php endif; ?>

        <br><br>
        <a class="boton" href="index.php">Volver al inicio</a>
    </div>
</body>
</html>
