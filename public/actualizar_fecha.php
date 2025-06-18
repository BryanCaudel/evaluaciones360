<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['evaluacion_id'], $_POST['nueva_fecha'])) {
        $evaluacion_id = intval($_POST['evaluacion_id']);
        $nueva_fecha = $_POST['nueva_fecha'];

        $stmt = $conexion->prepare("UPDATE evaluaciones SET fecha = ? WHERE id = ?");
        $stmt->bind_param("si", $nueva_fecha, $evaluacion_id);

        if ($stmt->execute()) {
            header("Location: dashboard.php?modo=evaluaciones");
            exit;
        } else {
            echo "Error al actualizar la fecha.";
        }
    } else {
        echo "Datos incompletos.";
    }
} else {
    header("Location: dashboard.php");
    exit;
}
