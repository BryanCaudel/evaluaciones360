<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'super_admin') {
    header("Location: index.php");
    exit;
}

require_once '../src/controllers/EmpresaController.php';
$controller = new EmpresaController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->crearEmpresa($_POST['nombre'], $_POST['descripcion']);
    header("Location: empresas.php");
    exit;
}

$empresas = $controller->listarEmpresas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Empresas</title>
</head>
<body>
    <h1>Gestión de Empresas</h1>

    <form method="POST">
        <input type="text" name="nombre" placeholder="Nombre de la empresa" required>
        <textarea name="descripcion" placeholder="Descripción"></textarea>
        <button type="submit">Crear Empresa</button>
    </form>

    <h2>Listado de Empresas</h2>
    <ul>
        <?php foreach ($empresas as $empresa): ?>
            <li>
                <?= htmlspecialchars($empresa['nombre']) ?> - <?= htmlspecialchars($empresa['descripcion']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
