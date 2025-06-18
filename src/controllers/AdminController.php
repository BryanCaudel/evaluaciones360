<?php
require "../config/database.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $contraseña = password_hash(trim($_POST['contraseña']), PASSWORD_BCRYPT);
    $empresaID = $_POST['empresa'];
    $rolID = 4; // Subadministrador

    $query = $db->prepare("SELECT * FROM usuarios WHERE user = ?");
    $query->bind_param("s", $usuario);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        echo "El usuario ya existe.";
    } else {
        $query = $db->prepare("INSERT INTO usuarios (password, RolID, user, EmpresaID) VALUES (?, ?, ?, ?)");
        $query->bind_param("sisi", $contraseña, $rolID, $usuario, $empresaID);
        if ($query->execute()) {
            echo "Subadministrador creado con éxito.";
        } else {
            echo "Error al crear subadministrador.";
        }
    }
}
