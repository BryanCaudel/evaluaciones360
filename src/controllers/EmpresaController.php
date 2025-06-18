<?php
require_once '../../config/database.php';

class EmpresaController {

    public function crearEmpresa($nombre, $descripcion) {
        global $conexion;
        $stmt = $conexion->prepare("INSERT INTO empresas (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        return $stmt->execute();
    }

    public function listarEmpresas() {
        global $conexion;
        $resultado = $conexion->query("SELECT * FROM empresas");
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function eliminarEmpresa($id) {
        global $conexion;
        $stmt = $conexion->prepare("DELETE FROM empresas WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
