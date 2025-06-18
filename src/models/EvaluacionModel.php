<?php
class EvaluacionModel {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getDimensiones() {
        $query = $this->db->query("SELECT * FROM dimensiones");
        return $query->fetch_all(MYSQLI_ASSOC);
    }

    public function getPreguntasByDimension($dimensionID) {
        $query = $this->db->prepare("SELECT * FROM preguntas WHERE DimensionID = ?");
        $query->bind_param("i", $dimensionID);
        $query->execute();
        return $query->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function saveRespuestas($evaluacionID, $preguntaID, $evaluadoID, $evaluadorUsuarioID, $respuesta, $comentario) {
        $query = $this->db->prepare("INSERT INTO respuestas (EvaluacionID, PreguntaID, EvaluadoID, EvaluadorAnonimoID, Puntuacion, Comentario) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
        $query->bind_param("iiiii", $evaluacionID, $preguntaID, $evaluadoID, $evaluadorUsuarioID, $respuesta, $comentario);
        $query->execute();
    }
}
