<?php 
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

$mensaje = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos actuales del usuario
$stmt = $conexion->prepare("SELECT id, user, empresa_id, rol_id FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();

if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

// Verifica que el admin_empresa no edite usuarios de otra empresa
if ($_SESSION['rol'] === 'admin_empresa' && $usuario['empresa_id'] != $_SESSION['empresa_id']) {
    echo "No tienes permiso para editar este usuario.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_usuario = trim($_POST['user']);
    $nuevo_rol_id = intval($_POST['rol_id']);
    $nueva_pass = $_POST['password'] ?? '';
    $nueva_empresa_id = $_SESSION['rol'] === 'super_admin' ? intval($_POST['empresa_id']) : $_SESSION['empresa_id'];

    // Verificar si otro usuario ya tiene ese nombre
    $stmt_verif = $conexion->prepare("SELECT id FROM usuarios WHERE user = ? AND id != ?");
    $stmt_verif->bind_param("si", $nuevo_usuario, $id);
    $stmt_verif->execute();
    $res_verif = $stmt_verif->get_result();

    if ($res_verif->num_rows > 0) {
        $mensaje = "El nombre de usuario ya está en uso.";
    } else {
        if (!empty($nueva_pass)) {
            $hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare("UPDATE usuarios SET user = ?, rol_id = ?, empresa_id = ?, password = ? WHERE id = ?");
            $stmt->bind_param("siisi", $nuevo_usuario, $nuevo_rol_id, $nueva_empresa_id, $hash, $id);
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET user = ?, rol_id = ?, empresa_id = ? WHERE id = ?");
            $stmt->bind_param("siii", $nuevo_usuario, $nuevo_rol_id, $nueva_empresa_id, $id);
        }

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            $mensaje = "Error al actualizar el usuario.";
        }
    }
}

// Obtener roles válidos
$roles = $conexion->query("SELECT id, nombre FROM roles WHERE nombre IN ('admin_empresa', 'evaluado')");

// Obtener empresas según el rol
if ($_SESSION['rol'] === 'super_admin') {
    $empresas = $conexion->query("SELECT id, nombre FROM empresas");
} else {
    $empresas = $conexion->query("SELECT id, nombre FROM empresas WHERE id = " . intval($_SESSION['empresa_id']));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <div class="form-container">
        <h2>Editar Usuario</h2>

        <?php if ($mensaje): ?>
            <p class="error"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Usuario:</label>
            <input type="text" name="user" value="<?= htmlspecialchars($usuario['user']) ?>" required>

            <label>Empresa:</label>
            <select name="empresa_id" required <?= $_SESSION['rol'] !== 'super_admin' ? 'disabled' : '' ?>>
                <?php while ($empresa = $empresas->fetch_assoc()): ?>
                    <option value="<?= $empresa['id'] ?>" <?= $empresa['id'] == $usuario['empresa_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($empresa['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <?php if ($_SESSION['rol'] !== 'super_admin'): ?>
                <input type="hidden" name="empresa_id" value="<?= $_SESSION['empresa_id'] ?>">
            <?php endif; ?>

            <label>Rol:</label>
            <select name="rol_id" required>
                <?php while ($rol = $roles->fetch_assoc()): ?>
                    <option value="<?= $rol['id'] ?>" <?= $rol['id'] == $usuario['rol_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Actualizar Contraseña:</label>
            <input type="password" name="password" placeholder="Déjalo en blanco si no deseas cambiarla">

            <button type="submit">Guardar Cambios</button>
        </form>

        <br>
        <a href="dashboard.php" class="button">← Volver</a>
    </div>
</body>
</html>
