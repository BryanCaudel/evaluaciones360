<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

$rol_usuario = $_SESSION['rol'];
$empresa_id = $_SESSION['empresa_id'] ?? null;
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../src/controllers/UsuarioController.php';

    $user = trim($_POST['user']);
    $password = $_POST['password'];
    $rol_id = intval($_POST['rol_id']);
    $empresa_id_form = $rol_usuario === 'super_admin' ? intval($_POST['empresa_id']) : $empresa_id;

    if (UsuarioController::crearUsuario($user, $password, $rol_id, $empresa_id_form)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $mensaje = "No se pudo crear el usuario. Verifica los datos o si ya existe.";
    }
}

$roles_validos = $rol_usuario === 'super_admin'
    ? $conexion->query("SELECT id, nombre FROM roles WHERE nombre IN ('admin_empresa', 'evaluado')")
    : $conexion->query("SELECT id, nombre FROM roles WHERE nombre IN ('admin_empresa', 'evaluado')");

$empresas = ($rol_usuario === 'super_admin') ? $conexion->query("SELECT id, nombre FROM empresas") : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Usuario</title>
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <div class="form-container">
        <h2>Agregar Usuario</h2>

        <?php if ($mensaje): ?>
            <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="user">Nombre de usuario:</label>
            <input type="text" name="user" id="user" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>

            <label for="rol_id">Rol:</label>
            <select name="rol_id" required>
                <option value="">Selecciona un rol</option>
                <?php while ($rol = $roles_validos->fetch_assoc()): ?>
                    <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                <?php endwhile; ?>
            </select>

            <?php if ($rol_usuario === 'super_admin'): ?>
                <label for="empresa_id">Empresa:</label>
                <select name="empresa_id" required>
                    <option value="">Selecciona empresa</option>
                    <?php while ($e = $empresas->fetch_assoc()): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>

            <button type="submit">Agregar Usuario</button>
        </form>
<br>
        <!-- Botón volver -->
        <div class="volver-container">
            <a href="dashboard.php" class="btn-volver">← Volver</a>
        </div>
    </div>
</body>
</html>
