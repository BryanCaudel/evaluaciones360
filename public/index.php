<?php 
session_start();
require_once '../src/config/database.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'];
    $password = $_POST['password'];

    $stmt = $conexion->prepare("SELECT u.id, u.password, u.empresa_id, u.user, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.user = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['empresa_id'] = $row['empresa_id'];
            $_SESSION['user'] = $row['user'];
            $_SESSION['rol'] = $row['rol'];

            if ($row['rol'] === 'super_admin' || $row['rol'] === 'admin_empresa') {
                header("Location: dashboard.php");
                exit;
            } elseif ($row['rol'] === 'evaluado') {
                $stmt_eval = $conexion->prepare("SELECT id, codigo_unico FROM evaluaciones WHERE evaluado_id = ? ORDER BY fecha DESC LIMIT 1");
                $stmt_eval->bind_param("i", $row['id']);
                $stmt_eval->execute();
                $eval = $stmt_eval->get_result();

                if ($eval->num_rows > 0) {
                    $evaluacion = $eval->fetch_assoc();
                    $_SESSION['evaluacion_id'] = $evaluacion['id'];
                    $_SESSION['evaluador_nombre'] = $row['user'];
                    $_SESSION['evaluador_relacion'] = 'Autoevaluación';
                    $_SESSION['codigo_temp'] = null;

                    header("Location: evaluar_preguntas.php");
                    exit;
                } else {
                    $mensaje = "No se encontró una evaluación asignada.";
                }
            } else {
                $mensaje = "Rol no permitido.";
            }
        } else {
            $mensaje = "Usuario o contraseña incorrectos.";
        }
    } else {
        $mensaje = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($mensaje): ?>
            <p class="error"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="user">Usuario:</label>
            <input type="text" name="user" id="user" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Ingresar</button>
        </form>

        <p class="extra-link">
            ¿Tienes un código de evaluación? <a href="ingresar_codigo.php">Realizar evaluación</a>
        </p>
    </div>
</body>
</html>
