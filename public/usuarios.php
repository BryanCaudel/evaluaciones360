<?php
session_start();
require "../src/config/database.php";

// Verificar si el usuario está logueado
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header("Location: index.php");
    exit();
}

// Obtener las empresas
$query = $db->query("SELECT EmpresaID, NombreEmpresa FROM empresas");
$empresas = $query->fetch_all(MYSQLI_ASSOC);

// Consultar los usuarios si se ha seleccionado una empresa
$usuarios = [];
if (isset($_POST['empresa'])) {
    $empresaID = $_POST['empresa'];

    // Consultar usuarios de la empresa seleccionada
    $query = $db->prepare("SELECT u.UsuarioID, u.user, r.NombreRol FROM usuarios u 
                           INNER JOIN roles r ON u.RolID = r.RolID 
                           WHERE u.EmpresaID = ?");
    $query->bind_param("i", $empresaID);
    $query->execute();
    $usuarios = $query->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Barra lateral -->
        <aside class="sidebar" id="sidebar">
    <h2>Mi Dashboard</h2>
    <nav>
        <ul>
            <li><a href="inicio.php">Inicio</a></li>
            <li><a href="usuarios.php">Usuarios</a></li>
            <li><a href="logout.php" class="logout">Cerrar Sesión</a></li>
        </ul>
    </nav>
</aside>


        <!-- Contenido principal -->
        <main class="main-content">
            <header class="main-header">
                <button id="toggleSidebar" class="toggle-btn">☰</button>
                <h1>Bienvenido, Administrador</h1>
                <p>Este es tu panel principal.</p>
            </header>

            <section class="content">
                <!-- Tarjeta para selección de empresa -->
                <div class="card select-card">
                    <form method="POST" action="usuarios.php" class="empresa-select-form">
                        <select name="empresa" id="empresa" class="select-empresa" required>
                            <option value="">Seleccionar Empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?= $empresa['EmpresaID']; ?>"><?= $empresa['NombreEmpresa']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-select">Ver Usuarios</button>
                    </form>
                </div>

                <?php if (count($usuarios) > 0): ?>
                    <!-- Tabla de usuarios de la empresa seleccionada -->
                    <table class="usuarios-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= $usuario['UsuarioID']; ?></td>
                                    <td><?= $usuario['user']; ?></td>
                                    <td><?= $usuario['NombreRol']; ?></td>
                                    <td>
                                        <a href="editarUsuario.php?id=<?= $usuario['UsuarioID']; ?>" class="btn-edit">Editar</a>
                                        <a href="eliminarUsuario.php?id=<?= $usuario['UsuarioID']; ?>" class="btn-delete" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (isset($_POST['empresa'])): ?>
                    <p>No hay usuarios registrados para esta empresa.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="assets/js/sidebar.js"></script>
</body>
</html>
