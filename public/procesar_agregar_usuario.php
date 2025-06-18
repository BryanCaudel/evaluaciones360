<?php
session_start();
require_once '../src/controllers/UsuarioController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $password = $_POST['password'] ?? '';
    $rol_id = intval($_POST['rol_id'] ?? 0);
    $empresa_id = isset($_POST['empresa_id']) ? intval($_POST['empresa_id']) : null;

    if (!empty($user) && !empty($password) && $rol_id > 0) {
        $resultado = UsuarioController::crearUsuario($user, $password, $rol_id, $empresa_id);

        if ($resultado) {
            header('Location: dashboard.php');
            exit;
        } else {
            echo "Error: El usuario ya existe o no se pudo guardar.";
        }
    } else {
        echo "Por favor completa todos los campos.";
    }
} else {
    header("Location: agregar_usuario.php");
    exit;
}
