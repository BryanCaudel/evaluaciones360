<?php
session_start();
require_once '../src/config/database.php';
require_once '../src/controllers/EvaluacionController.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$rol = $_SESSION['rol'];

// Guardar empresa seleccionada si es super admin
if ($rol === 'super_admin' && isset($_POST['empresa_id'])) {
    $_SESSION['empresa_seleccionada'] = intval($_POST['empresa_id']);
}

// Guardar usuario preseleccionado desde botón del dashboard
if (isset($_POST['preselect_user'])) {
    $_SESSION['preselect_user'] = intval($_POST['preselect_user']);

    // Si es super admin, obtener empresa de ese usuario
    if ($rol === 'super_admin') {
        $stmt = $conexion->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['preselect_user']);
        $stmt->execute();
        $empresa_row = $stmt->get_result()->fetch_assoc();
        $_SESSION['empresa_seleccionada'] = $empresa_row['empresa_id'] ?? null;
    }
}

$preselect_user = $_SESSION['preselect_user'] ?? null;
$empresa_id = ($rol === 'admin_empresa') ? $_SESSION['empresa_id'] : ($_SESSION['empresa_seleccionada'] ?? null);

// Guardar evaluación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluado_id'], $_POST['fecha'])) {
    $fecha = $_POST['fecha'];
    $evaluado_id = intval($_POST['evaluado_id']);

    $stmtNombre = $conexion->prepare("SELECT user FROM usuarios WHERE id = ?");
    $stmtNombre->bind_param("i", $evaluado_id);
    $stmtNombre->execute();
    $resNombre = $stmtNombre->get_result();
    $row = $resNombre->fetch_assoc();

    if (!$row) {
        $mensaje = "No se pudo obtener el nombre del evaluado.";
    } else {
        $nombre_evaluado = $row['user'];
        $empresa_id = $rol === 'admin_empresa' ? $_SESSION['empresa_id'] : intval($_POST['empresa_id']);
        $creada_por = $_SESSION['usuario_id'];
        $codigo = EvaluacionController::generarCodigo();

        $stmt = $conexion->prepare("INSERT INTO evaluaciones (empresa_id, nombre_evaluado, evaluado_id, fecha, codigo_unico, creada_por) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissi", $empresa_id, $nombre_evaluado, $evaluado_id, $fecha, $codigo, $creada_por);

        if ($stmt->execute()) {
            unset($_SESSION['preselect_user']);
            header("Location: dashboard.php");
            exit;
        } else {
            $mensaje = "Error al guardar la evaluación.";
        }
    }
}

if ($rol === 'super_admin') {
    $empresas = $conexion->query("SELECT id, nombre FROM empresas");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programar Evaluación</title>
    <link rel="stylesheet" href="assets/css/formularios.css">
    <style>
        .volver-boton {
            display: block;
            margin-top: 25px;
            padding: 10px 20px;
            background: #ccc;
            color: #333;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            width: fit-content;
            transition: background 0.3s ease;
        }
        .volver-boton:hover {
            background: #aaa;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Programar Evaluación</h2>

    <?php if ($mensaje): ?>
        <p class="error"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($rol === 'super_admin' && !$preselect_user): ?>
        <form method="POST">
            <label>Empresa:</label>
            <select name="empresa_id" onchange="this.form.submit()" required>
                <option value="">--Selecciona--</option>
                <?php while ($empresa = $empresas->fetch_assoc()): ?>
                    <option value="<?= $empresa['id'] ?>" <?= (isset($_SESSION['empresa_seleccionada']) && $_SESSION['empresa_seleccionada'] == $empresa['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($empresa['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php
    if ($empresa_id):
        $q = $conexion->prepare("SELECT id, user FROM usuarios WHERE rol_id = 4 AND empresa_id = ?");
        $q->bind_param("i", $empresa_id);
        $q->execute();
        $usuarios = $q->get_result();
    ?>
        <form method="POST">
            <input type="hidden" name="empresa_id" value="<?= $empresa_id ?>">

            <label>Selecciona Evaluado:</label>
            <select name="evaluado_id" required>
                <option value="">--Selecciona--</option>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>" <?= ($preselect_user && $preselect_user == $u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['user']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fecha de Evaluación:</label>
            <input type="date" name="fecha" required>

            <button type="submit">Guardar Evaluación</button>
        </form>
    <?php endif; ?>

    <a href="dashboard.php" class="volver-boton">← Volver</a>
</div>
</body>
</html>
