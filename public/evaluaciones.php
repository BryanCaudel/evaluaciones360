<?php
session_start();
require "../src/config/database.php";

// Verificar si el usuario está logueado y es de tipo Evaluador
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true || $_SESSION['tipoUsuario'] != 2) {
    header("Location: index.php");
    exit();
}

// Consultar los usuarios de la misma empresa que el evaluador (ID de empresa ya asignado al evaluador)
$empresaID = $_SESSION['idEmpresa']; // El evaluador tiene asignado su empresa
$query = $db->prepare("SELECT u.UsuarioID, u.user, r.NombreRol FROM usuarios u 
                       INNER JOIN roles r ON u.RolID = r.RolID 
                       WHERE u.EmpresaID = ? AND r.RolID = 3"); // Solo los de tipo "Evaluado"
$query->bind_param("i", $empresaID);
$query->execute();
$usuarios = $query->get_result()->fetch_all(MYSQLI_ASSOC);

// Si se ha seleccionado un evaluado
if (isset($_POST['evaluado'])) {
    $_SESSION['evaluadoID'] = $_POST['evaluado']; // Guardamos el usuario seleccionado para evaluar
}

// Si se ha seleccionado la relación
if (isset($_POST['evaluador_tipo'])) {
    $_SESSION['evaluador_tipo'] = $_POST['evaluador_tipo']; // Guardamos la relación seleccionada
}

// Consultar dimensiones para la evaluación si ya se ha seleccionado la relación
$dimensiones = [];
if (isset($_SESSION['evaluador_tipo'])) {
    $queryDim = $db->query("SELECT * FROM dimensiones");
    $dimensiones = $queryDim->fetch_all(MYSQLI_ASSOC);
}

// Si se ha seleccionado una dimensión
if (isset($_POST['dimension_id'])) {
    $_SESSION['dimension_id'] = $_POST['dimension_id']; // Guardamos la dimensión seleccionada
    $dimensionID = $_SESSION['dimension_id'];
    $queryPreg = $db->prepare("SELECT * FROM preguntas WHERE DimensionID = ?");
    $queryPreg->bind_param("i", $dimensionID);
    $queryPreg->execute();
    $resultPreg = $queryPreg->get_result();
    $preguntas = $resultPreg->fetch_all(MYSQLI_ASSOC);
}

// Guardar las respuestas
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respuesta'])) {
    foreach ($_POST['respuesta'] as $preguntaID => $respuesta) {
        $query = $db->prepare("INSERT INTO respuestas (EvaluacionID, PreguntaID, EvaluadoID, EvaluadorAnonimoID, Puntuacion, Comentario)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("iiiii", $_SESSION['evaluacionID'], $preguntaID, $_SESSION['evaluadoID'], $_SESSION['idUsuario'], $respuesta, $_POST['comentario'][$preguntaID]);
        $query->execute();
    }
    header("Location: evaluacion.php"); // Redirige a la siguiente evaluación o muestra éxito
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación 360</title>
    <link rel="stylesheet" href="assets/css/evaluaciones.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="main-content">
            <header class="main-header">
                <h1>Evaluación 360 Grados</h1>
                <p>Aquí puedes realizar la evaluación.</p>
            </header>

            <section class="content">
                <?php if (!isset($_SESSION['evaluadoID'])): ?>
                    <!-- Formulario de selección de usuario a evaluar -->
                    <form method="POST" action="evaluaciones.php">
                        <label for="evaluado">Selecciona el usuario a evaluar:</label>
                        <select name="evaluado" id="evaluado" required>
                            <option value="">Seleccionar usuario</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['UsuarioID']; ?>"><?= $usuario['user']; ?> - <?= $usuario['NombreRol']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-select">Seleccionar Usuario</button>
                    </form>
                <?php elseif (isset($_SESSION['evaluadoID']) && !isset($_SESSION['evaluador_tipo'])): ?>
                    <!-- Formulario de selección de relación con el evaluado -->
                    <form method="POST" action="evaluaciones.php" id="relation-form">
                        <label for="evaluador_tipo">¿Quién eres en relación con el evaluado?</label>
                        <select name="evaluador_tipo" id="evaluador_tipo" required>
                            <option value="">Seleccionar relación</option>
                            <option value="jefe">Mi jefe</option>
                            <option value="colega">Mi colega</option>
                            <option value="colaborador">Mi colaborador</option>
                        </select>
                        <button type="submit" class="btn-select" id="submit-btn">Seleccionar</button>
                    </form>
                <?php endif; ?>

                <?php if (isset($_SESSION['evaluador_tipo']) && !isset($_SESSION['dimension_id'])): ?>
                    <!-- Dimensiones a evaluar -->
                    <form method="POST" action="evaluaciones.php" id="dimension-form">
                        <h2>Dimensiones para la Evaluación</h2>
                        <table class="dimensiones-table">
                            <thead>
                                <tr>
                                    <th>Nombre de la Dimensión</th>
                                    <th>Seleccionar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dimensiones as $dim): ?>
                                    <tr>
                                        <td><?= $dim['NombreDimension']; ?></td>
                                        <td>
                                            <button type="submit" name="dimension_id" value="<?= $dim['DimensionID']; ?>" class="btn-select">Seleccionar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                <?php endif; ?>

                <?php if (isset($_SESSION['dimension_id']) && isset($preguntas)): ?>
                    <!-- Mostrar preguntas de la dimensión seleccionada -->
                    <form method="POST" action="evaluaciones.php">
                        <h2>Responde las preguntas</h2>
                        <?php foreach ($preguntas as $pregunta): ?>
                            <div>
                                <p><?= $pregunta['TextoPregunta']; ?></p>
                                <input type="radio" name="respuesta[<?= $pregunta['PreguntaID']; ?>]" value="1" required> 1
                                <input type="radio" name="respuesta[<?= $pregunta['PreguntaID']; ?>]" value="2"> 2
                                <input type="radio" name="respuesta[<?= $pregunta['PreguntaID']; ?>]" value="3"> 3
                                <input type="radio" name="respuesta[<?= $pregunta['PreguntaID']; ?>]" value="4"> 4
                                <input type="radio" name="respuesta[<?= $pregunta['PreguntaID']; ?>]" value="5"> 5
                                <textarea name="comentario[<?= $pregunta['PreguntaID']; ?>]" placeholder="Escribe tu comentario"></textarea>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-submit">Guardar respuestas</button>
                    </form>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="assets/js/sidebar.js"></script>

    <script>
        // Ocultar la tabla de dimensiones cuando se seleccione una dimensión
        const dimensionForm = document.getElementById("dimension-form");
        const preguntasForm = document.getElementById("questions-form");

        if (dimensionForm) {
            dimensionForm.addEventListener("submit", function() {
                dimensionForm.style.display = "none";  // Ocultar la tabla de dimensiones
            });
        }
    </script>
</body>
</html>
