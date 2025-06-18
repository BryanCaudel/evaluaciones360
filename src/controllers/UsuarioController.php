<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioController {
    public static function crearUsuario($user, $password, $rol_id, $empresa_id) {
        global $conexion;

        // Verificar si el usuario ya existe
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE user = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            return false; // Usuario duplicado
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $insert = $conexion->prepare("INSERT INTO usuarios (user, password, rol_id, empresa_id) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssii", $user, $hash, $rol_id, $empresa_id);

        return $insert->execute();
    }
}
