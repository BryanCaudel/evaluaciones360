<?php
require_once __DIR__ . '/../config/database.php';

class AuthController {
    public static function login($user, $password) {
        global $conexion;

        $stmt = $conexion->prepare("SELECT u.id, u.password, u.empresa_id, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.user = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $id = $row['id'];
            $passwordHash = $row['password'];
            $empresa_id = $row['empresa_id'];
            $rol_nombre = $row['rol'];

            if (password_verify($password, $passwordHash)) {
                session_start();
                $_SESSION['usuario_id'] = $id;
                $_SESSION['empresa_id'] = $empresa_id;
                $_SESSION['rol'] = $rol_nombre;
                $_SESSION['user'] = $user;

                // Si el usuario es evaluado, redirigir a la autoevaluación
                if ($rol_nombre === 'evaluado') {
                    $stmtEval = $conexion->prepare("SELECT id FROM evaluaciones WHERE evaluado_id = ?");
                    $stmtEval->bind_param("i", $id);
                    $stmtEval->execute();
                    $resEval = $stmtEval->get_result();

                    if ($resEval->num_rows > 0) {
                        $evaluacion = $resEval->fetch_assoc();
                        $evaluacion_id = $evaluacion['id'];

                        // Verificar si ya respondió todas las preguntas (Autoevaluación)
                        $check = $conexion->prepare("
                            SELECT COUNT(*) AS pendientes
                            FROM evaluacion_preguntas ep
                            WHERE ep.evaluacion_id = ?
                            AND ep.id NOT IN (
                                SELECT evaluacion_pregunta_id
                                FROM autoevaluaciones
                                WHERE evaluacion_id = ?
                            )
                        ");
                        $check->bind_param("ii", $evaluacion_id, $evaluacion_id);
                        $check->execute();
                        $pendientes = $check->get_result()->fetch_assoc()['pendientes'];

                        if ($pendientes == 0) {
                            header("Location: gracias.php");
                            exit;
                        }

                        $_SESSION['evaluacion_id'] = $evaluacion_id;
                        $_SESSION['evaluador_nombre'] = $user;
                        $_SESSION['evaluador_relacion'] = 'Autoevaluación';

                        header("Location: evaluar_preguntas.php");
                        exit;
                    } else {
                        header("Location: evaluado_codigo.php");
                        exit;
                    }
                } else {
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }

        header("Location: index.php?error=1");
        exit;
    }

    public static function logout() {
        session_start();
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
