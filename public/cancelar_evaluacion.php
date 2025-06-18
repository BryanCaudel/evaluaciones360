<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "ID de evaluación no proporcionado.";
    exit;
}

$id = intval($_GET['id']);

// Verificación de permisos
$stmt = $conexion->prepare("SELECT empresa_id FROM evaluaciones WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$evaluacion = $res->fetch_assoc();

if (!$evaluacion) {
    echo "Evaluación no encontrada.";
    exit;
}

if ($_SESSION['rol'] === 'admin_empresa' && $_SESSION['empresa_id'] != $evaluacion['empresa_id']) {
    echo "No tienes permiso para cancelar esta evaluación.";
    exit;
}

// Eliminar evaluación y sus respuestas
$conexion->query("DELETE FROM respuestas WHERE evaluacion_id = $id");
$conexion->query("DELETE FROM autoevaluaciones WHERE evaluacion_id = $id");
$conexion->query("DELETE FROM evaluacion_preguntas WHERE evaluacion_id = $id");
$conexion->query("DELETE FROM sesiones_evaluadores WHERE evaluacion_id = $id");

$stmt = $conexion->prepare("DELETE FROM evaluaciones WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: dashboard.php?empresa_id={$evaluacion['empresa_id']}&modo=evaluaciones");
exit;
?>
