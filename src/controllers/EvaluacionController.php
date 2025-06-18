<?php
require_once __DIR__ . '/../config/database.php';

class EvaluacionController {

    public static function generarCodigo() {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 10);
    }

    public static function crearEvaluacion($empresa_id, $evaluado_id, $fecha, $creada_por) {
        global $conexion;
        $codigo = self::generarCodigo();

        $stmt = $conexion->prepare("INSERT INTO evaluaciones (empresa_id, evaluado_id, fecha, codigo_unico, creada_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $empresa_id, $evaluado_id, $fecha, $codigo, $creada_por);

        if (!$stmt->execute()) {
            return false;
        }

        $evaluacion_id = $stmt->insert_id;

        $preguntas = $conexion->query("SELECT id FROM preguntas");
        while ($p = $preguntas->fetch_assoc()) {
            $conexion->query("INSERT INTO evaluacion_preguntas (evaluacion_id, pregunta_id) VALUES ($evaluacion_id, {$p['id']})");
        }

        return true;
    }
}
